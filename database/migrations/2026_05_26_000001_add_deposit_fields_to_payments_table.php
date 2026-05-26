<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payments', 'total_amount')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->decimal('total_amount', 12, 2)->nullable();
            });
        }

        if (! Schema::hasColumn('payments', 'deposit_amount')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->decimal('deposit_amount', 12, 2)->nullable();
            });
        }

        if (! Schema::hasColumn('payments', 'deposit_paid_at')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->timestamp('deposit_paid_at')->nullable();
            });
        }

        DB::table('payments')
            ->orderBy('id')
            ->select(['id', 'amount', 'status', 'paid_at'])
            ->chunkById(100, function ($payments): void {
                foreach ($payments as $payment) {
                    DB::table('payments')
                        ->where('id', $payment->id)
                        ->update([
                            'total_amount' => $payment->amount,
                            'deposit_amount' => $payment->amount,
                            'deposit_paid_at' => $payment->status === 'paid' ? $payment->paid_at : null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        $columns = collect(['total_amount', 'deposit_amount', 'deposit_paid_at'])
            ->filter(fn (string $column): bool => Schema::hasColumn('payments', $column))
            ->all();

        if ($columns === []) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
