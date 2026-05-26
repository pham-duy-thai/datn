<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->with(['appointment.doctor.department', 'appointment.service', 'user'])
            ->when($request->filled('method'), fn ($query) => $query->where('method', $request->string('method')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($payments);
    }

    public function show(Payment $payment): JsonResponse
    {
        return response()->json([
            'data' => $payment->load(['appointment.doctor.department', 'appointment.service', 'user']),
        ]);
    }

    public function markCashPaid(Request $request, Payment $payment, PaymentGatewayService $gateway): JsonResponse
    {
        $data = $request->validate([
            'transaction_code' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json([
            'data' => $gateway->markCashPaid($payment, $data['transaction_code'] ?? null),
        ]);
    }

    public function cancel(Payment $payment, PaymentGatewayService $gateway): JsonResponse
    {
        return response()->json(['data' => $gateway->cancel($payment)]);
    }

    public function vnpayIpn(Request $request, PaymentGatewayService $gateway): JsonResponse
    {
        try {
            $result = $gateway->handleVnpayIpn($request->query());
        } catch (Throwable) {
            $result = ['RspCode' => '99', 'Message' => 'Unknow error'];
        }

        return response()->json($result);
    }

    public function momoIpn(Request $request, PaymentGatewayService $gateway): JsonResponse
    {
        $payment = $gateway->handleMomoResult($request->all());

        return response()->json([
            'resultCode' => 0,
            'message' => 'Đã nhận IPN MoMo.',
            'payment_id' => $payment->id,
            'status' => $payment->status,
        ]);
    }
}
