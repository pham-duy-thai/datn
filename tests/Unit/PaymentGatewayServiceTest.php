<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\Payment;
use App\Services\PaymentGatewayService;
use Tests\TestCase;

class PaymentGatewayServiceTest extends TestCase
{
    public function test_paid_payment_confirms_appointment(): void
    {
        $appointment = new class(['status' => 'pending']) extends Appointment {
            public function update(array $attributes = [], array $options = []): bool
            {
                $this->fill($attributes);

                return true;
            }
        };

        $payment = new class(['status' => 'pending']) extends Payment {
            public function update(array $attributes = [], array $options = []): bool
            {
                $this->fill($attributes);

                return true;
            }

            public function fresh($with = []): ?Payment
            {
                return $this;
            }
        };

        $payment->setRelation('appointment', $appointment);

        app(PaymentGatewayService::class)->markCashPaid($payment, 'TEST-PAID');

        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->deposit_paid_at);
        $this->assertSame('confirmed', $appointment->status);
    }
}
