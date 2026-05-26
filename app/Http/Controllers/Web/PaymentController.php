<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentGatewayService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentController extends Controller
{
    public function show(Request $request, Payment $payment): View
    {
        $this->authorizePaymentAccess($request, $payment);

        return view('pages.payments.show', [
            'payment' => $payment->load(['appointment.doctor.department', 'appointment.service', 'user']),
        ]);
    }

    public function retry(Request $request, Payment $payment, PaymentGatewayService $gateway): RedirectResponse
    {
        $this->authorizePaymentAccess($request, $payment);

        if (! in_array($payment->method, ['vnpay', 'momo'], true) || in_array($payment->status, ['paid', 'cancelled'], true)) {
            return redirect()
                ->route('payments.show', $payment)
                ->with('error', 'Thanh toán này không thể thực hiện lại.');
        }

        try {
            return redirect()->away($gateway->checkoutUrl($payment, $request));
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('payments.show', $payment)
                ->with('error', $exception->getMessage());
        }
    }

    public function vnpayReturn(Request $request, PaymentGatewayService $gateway): View
    {
        try {
            $payment = $gateway->handleVnpayReturn($request->query());
            $message = $payment->isPaid()
                ? 'Đặt lịch thành công. Thanh toán qua VNPay sandbox đã hoàn tất và lịch hẹn đã được xác nhận.'
                : 'Thanh toán qua VNPay sandbox chưa thành công.';
        } catch (RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        return view('pages.payments.result', compact('payment', 'message'));
    }

    public function momoReturn(Request $request, PaymentGatewayService $gateway): View
    {
        try {
            $payment = $gateway->handleMomoResult($request->query());
            $message = $payment->isPaid()
                ? 'Đặt lịch thành công. Thanh toán qua MoMo sandbox đã hoàn tất và lịch hẹn đã được xác nhận.'
                : 'Thanh toán qua MoMo sandbox chưa thành công.';
        } catch (RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        return view('pages.payments.result', compact('payment', 'message'));
    }

    public function markCashPaid(Request $request, Payment $payment, PaymentGatewayService $gateway): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['admin', 'receptionist'], true), 403);

        $data = $request->validate([
            'transaction_code' => ['nullable', 'string', 'max:255'],
        ]);

        $gateway->markCashPaid($payment, $data['transaction_code'] ?? null);

        return back()->with('success', "Đã xác nhận thanh toán #{$payment->id}.");
    }

    public function cancel(Request $request, Payment $payment, PaymentGatewayService $gateway): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['admin', 'receptionist'], true), 403);

        $gateway->cancel($payment);

        return back()->with('success', "Đã hủy thanh toán #{$payment->id}.");
    }

    private function authorizePaymentAccess(Request $request, Payment $payment): void
    {
        abort_unless(
            in_array($request->user()->role, ['admin', 'receptionist'], true)
                || $payment->user_id === $request->user()->id,
            403
        );
    }
}
