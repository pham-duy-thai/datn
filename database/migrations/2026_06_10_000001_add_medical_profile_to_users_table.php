<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('blood_type', 10)->nullable()->after('address');
            $table->text('allergies')->nullable()->after('blood_type');
            $table->text('underlying_conditions')->nullable()->after('allergies');
            $table->text('current_medications')->nullable()->after('underlying_conditions');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['blood_type', 'allergies', 'underlying_conditions', 'current_medications']);
        });
    }
};
