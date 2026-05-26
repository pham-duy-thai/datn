<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentGatewayService
{
    public function checkoutUrl(Payment $payment, Request $request): string
    {
        return match ($payment->method) {
            'vnpay' => $this->vnpayCheckoutUrl($payment, $request),
            'momo' => $this->momoCheckoutUrl($payment),
            default => route('payments.show', $payment),
        };
    }

    public function vnpayCheckoutUrl(Payment $payment, Request $request): string
    {
        $config = config('payments.vnpay');
        $this->assertConfigured($config, ['payment_url', 'tmn_code', 'hash_secret'], 'VNPay');

        $payment = $this->ensureGatewayOrderId($payment, numericOnly: true);

        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $config['tmn_code'],
            'vnp_Amount' => (int) round((float) $payment->amount * 100),
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $payment->gateway_order_id,
            'vnp_OrderInfo' => 'Thanh toan tien dat coc lich hen '.$payment->appointment_id,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => $config['return_url'] ?: route('payments.vnpay.return'),
            'vnp_IpAddr' => $request->ip(),
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_ExpireDate' => now()->addMinutes(15)->format('YmdHis'),
        ];

        ksort($params);
        $hashData = $this->vnpayQueryString($params);
        $secureHash = hash_hmac('sha512', $hashData, trim((string) $config['hash_secret']));
        $this->logVnpayDebug($params, $hashData, $secureHash);

        return rtrim($config['payment_url'], '?').'?'.$hashData.'&vnp_SecureHash='.$secureHash;
    }

    public function momoCheckoutUrl(Payment $payment): string
    {
        $config = config('payments.momo');
        $this->assertConfigured($config, ['endpoint', 'partner_code', 'access_key', 'secret_key'], 'MoMo');

        $payment = $this->ensureMomoOrderId($payment);
        $appointment = $payment->appointment;
        $requestId = $payment->gateway_order_id.'-'.Str::upper(Str::random(6));
        $redirectUrl = $config['return_url'] ?: route('payments.momo.return');
        $ipnUrl = $config['ipn_url'] ?: route('api.payments.momo.ipn');
        $extraData = base64_encode(json_encode(['payment_id' => $payment->id], JSON_THROW_ON_ERROR));
        $requestType = 'captureWallet';
        $amount = (string) (int) round((float) $payment->amount);
        $orderInfo = 'Thanh toan tien dat coc lich hen #'.$payment->appointment_id;

        $rawSignature = 'accessKey='.$config['access_key']
            .'&amount='.$amount
            .'&extraData='.$extraData
            .'&ipnUrl='.$ipnUrl
            .'&orderId='.$payment->gateway_order_id
            .'&orderInfo='.$orderInfo
            .'&partnerCode='.$config['partner_code']
            .'&redirectUrl='.$redirectUrl
            .'&requestId='.$requestId
            .'&requestType='.$requestType;

        $signature = hash_hmac('sha256', $rawSignature, $config['secret_key']);

        $payload = [
            'partnerCode' => $config['partner_code'],
            'partnerName' => config('app.name', 'Hospital'),
            'storeId' => 'HospitalSandbox',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $payment->gateway_order_id,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'requestType' => $requestType,
            'autoCapture' => true,
            'extraData' => $extraData,
            'userInfo' => [
                'name' => $appointment?->patient_name,
                'phoneNumber' => $appointment?->patient_phone,
                'email' => $appointment?->patient_email,
            ],
            'signature' => $signature,
        ];

        $response = Http::timeout(20)->post($config['endpoint'], $payload);
        $body = $response->json() ?: [];

        $payment->update([
            'gateway_response' => [
                'create_request' => $payload,
                'create_response' => $body,
            ],
        ]);

        if (! $response->successful() || empty($body['payUrl'])) {
            throw new RuntimeException($body['message'] ?? 'Không tạo được URL thanh toán MoMo sandbox.');
        }

        return $body['payUrl'];
    }

    public function handleVnpayReturn(array $data): Payment
    {
        [$valid, $payload] = $this->verifyVnpayPayload($data);

        $payment = Payment::where('gateway_order_id', $payload['vnp_TxnRef'] ?? null)->firstOrFail();
        $success = $valid && ($payload['vnp_ResponseCode'] ?? null) === '00' && ($payload['vnp_TransactionStatus'] ?? null) === '00';

        $this->finishGatewayPayment(
            payment: $payment,
            status: $success ? 'paid' : 'failed',
            transactionCode: $payload['vnp_TransactionNo'] ?? null,
            response: ['valid_signature' => $valid, 'return' => $payload]
        );

        return $payment->fresh(['appointment.service', 'user']);
    }

    public function handleVnpayIpn(array $data): array
    {
        if ($data === []) {
            return ['RspCode' => '99', 'Message' => 'Input data required'];
        }

        [$valid, $payload] = $this->verifyVnpayPayload($data);

        if (! $valid) {
            return ['RspCode' => '97', 'Message' => 'Invalid signature'];
        }

        $txnRef = $payload['vnp_TxnRef'] ?? null;
        if (! $txnRef) {
            return ['RspCode' => '01', 'Message' => 'Order not found'];
        }

        $payment = Payment::where('gateway_order_id', $txnRef)->first();
        if (! $payment) {
            return ['RspCode' => '01', 'Message' => 'Order not found'];
        }

        $expectedAmount = (int) round((float) $payment->amount * 100);
        if ((int) ($payload['vnp_Amount'] ?? 0) !== $expectedAmount) {
            return ['RspCode' => '04', 'Message' => 'invalid amount'];
        }

        if (! in_array($payment->status, ['unpaid', 'pending'], true)) {
            return ['RspCode' => '02', 'Message' => 'Order already confirmed'];
        }

        $success = ($payload['vnp_ResponseCode'] ?? null) === '00'
            && ($payload['vnp_TransactionStatus'] ?? null) === '00';

        $this->finishGatewayPayment(
            payment: $payment,
            status: $success ? 'paid' : 'failed',
            transactionCode: $payload['vnp_TransactionNo'] ?? null,
            response: ['valid_signature' => true, 'ipn' => $payload]
        );

        return ['RspCode' => '00', 'Message' => 'Confirm Success'];
    }

    public function handleMomoResult(array $data): Payment
    {
        $config = config('payments.momo');
        $this->assertConfigured($config, ['access_key', 'secret_key'], 'MoMo');

        $signature = $data['signature'] ?? '';
        $rawSignature = 'accessKey='.$config['access_key']
            .'&amount='.($data['amount'] ?? '')
            .'&extraData='.($data['extraData'] ?? '')
            .'&message='.($data['message'] ?? '')
            .'&orderId='.($data['orderId'] ?? '')
            .'&orderInfo='.($data['orderInfo'] ?? '')
            .'&orderType='.($data['orderType'] ?? '')
            .'&partnerCode='.($data['partnerCode'] ?? '')
            .'&payType='.($data['payType'] ?? '')
            .'&requestId='.($data['requestId'] ?? '')
            .'&responseTime='.($data['responseTime'] ?? '')
            .'&resultCode='.($data['resultCode'] ?? '')
            .'&transId='.($data['transId'] ?? '');

        $valid = hash_equals(hash_hmac('sha256', $rawSignature, $config['secret_key']), $signature);
        $payment = Payment::where('gateway_order_id', $data['orderId'] ?? null)->firstOrFail();
        $success = $valid && (string) ($data['resultCode'] ?? '') === '0';

        $this->finishGatewayPayment(
            payment: $payment,
            status: $success ? 'paid' : 'failed',
            transactionCode: isset($data['transId']) ? (string) $data['transId'] : null,
            response: ['valid_signature' => $valid, 'return' => $data]
        );

        return $payment->fresh(['appointment.service', 'user']);
    }

    public function markCashPaid(Payment $payment, ?string $transactionCode = null): Payment
    {
        $this->finishGatewayPayment(
            payment: $payment,
            status: 'paid',
            transactionCode: $transactionCode ?: 'CASH-'.$payment->id.'-'.now()->format('YmdHis'),
            response: ['method' => 'cash', 'confirmed_at' => now()->toDateTimeString()]
        );

        return $payment->fresh(['appointment.service', 'user']);
    }

    public function cancel(Payment $payment): Payment
    {
        $payment->update(['status' => 'cancelled']);
        $payment->appointment?->update(['status' => 'cancelled']);

        return $payment->fresh(['appointment.service', 'user']);
    }

    private function finishGatewayPayment(Payment $payment, string $status, ?string $transactionCode, array $response): void
    {
        $payment->update([
            'status' => $status,
            'transaction_code' => $transactionCode,
            'gateway_response' => array_merge($payment->gateway_response ?? [], $response),
            'paid_at' => $status === 'paid' ? now() : $payment->paid_at,
            'deposit_paid_at' => $status === 'paid' ? now() : $payment->deposit_paid_at,
        ]);

        if ($status === 'paid') {
            $payment->appointment?->update(['status' => 'confirmed']);
        }
    }

    private function ensureGatewayOrderId(Payment $payment, bool $numericOnly = false): Payment
    {
        if (! $payment->gateway_order_id || ($numericOnly && ! ctype_digit($payment->gateway_order_id))) {
            $payment->update([
                'gateway_order_id' => $numericOnly
                    ? now()->format('YmdHis').str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT).random_int(1000, 9999)
                    : 'HOSP'.$payment->id.now()->format('YmdHis').Str::upper(Str::random(4)),
            ]);
        }

        return $payment->fresh(['appointment']);
    }

    private function ensureMomoOrderId(Payment $payment): Payment
    {
        if (! $payment->gateway_order_id || in_array($payment->status, ['pending', 'failed'], true)) {
            $payment->update([
                'gateway_order_id' => 'MM'.$payment->id.now()->format('YmdHis').Str::upper(Str::random(4)),
            ]);
        }

        return $payment->fresh(['appointment']);
    }

    private function verifyVnpayPayload(array $data): array
    {
        $config = config('payments.vnpay');
        $this->assertConfigured($config, ['hash_secret'], 'VNPay');

        $payload = collect($data)
            ->filter(fn ($value, $key) => str_starts_with((string) $key, 'vnp_'))
            ->all();

        $secureHash = $payload['vnp_SecureHash'] ?? '';
        unset($payload['vnp_SecureHash'], $payload['vnp_SecureHashType']);
        ksort($payload);

        $hashData = $this->vnpayQueryString($payload);
        $valid = hash_equals(hash_hmac('sha512', $hashData, trim((string) $config['hash_secret'])), $secureHash);

        return [$valid, $payload];
    }

    private function vnpayQueryString(array $params): string
    {
        $pairs = [];

        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $pairs[] = urlencode((string) $key).'='.urlencode((string) $value);
        }

        return implode('&', $pairs);
    }

    private function logVnpayDebug(array $params, string $hashData, string $secureHash): void
    {
        if (! config('app.debug')) {
            return;
        }

        Log::info('VNPAY params', $params);
        Log::info('VNPAY hashData', ['data' => $hashData]);
        Log::info('VNPAY secureHash', ['hash' => $secureHash]);
    }

    private function assertConfigured(array $config, array $keys, string $gateway): void
    {
        foreach ($keys as $key) {
            if (blank($config[$key] ?? null)) {
                throw new RuntimeException("Chưa cấu hình {$gateway} sandbox: {$key}.");
            }
        }
    }
}
