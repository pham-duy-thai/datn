<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('method', ['cash', 'vnpay', 'momo']);
            $table->enum('status', ['unpaid', 'pending', 'paid', 'failed', 'cancelled', 'refunded'])->default('unpaid');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('VND');
            $table->string('transaction_code')->nullable();
            $table->string('gateway_order_id')->nullable()->unique();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['method', 'status']);
        });

        $this->backfillExistingAppointments();
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }

    private function backfillExistingAppointments(): void
    {
        if (! Schema::hasTable('appointments') || ! Schema::hasTable('services')) {
            return;
        }

        $now = now();
        $appointments = DB::table('appointments')
            ->leftJoin('services', 'appointments.service_id', '=', 'services.id')
            ->select([
                'appointments.id',
                'appointments.user_id',
                'appointments.status',
                'services.price',
            ])
            ->orderBy('appointments.id')
            ->get();

        foreach ($appointments as $index => $appointment) {
            $method = ['cash', 'vnpay', 'momo'][$index % 3];
            $paid = $appointment->status === 'completed' || ($method !== 'cash' && $appointment->status === 'confirmed');

            DB::table('payments')->insert([
                'appointment_id' => $appointment->id,
                'user_id' => $appointment->user_id,
                'method' => $method,
                'status' => $paid ? 'paid' : ($method === 'cash' ? 'unpaid' : 'pending'),
                'amount' => $appointment->price ?? 0,
                'currency' => 'VND',
                'transaction_code' => $paid ? 'SEED-'.$appointment->id : null,
                'gateway_order_id' => 'HOSP-SEED-'.$appointment->id,
                'gateway_response' => null,
                'paid_at' => $paid ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
