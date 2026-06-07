<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medical_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('modality')->default('xray');
            $table->string('body_part')->nullable();
            $table->string('image_path');
            $table->string('annotated_image_path')->nullable();
            $table->string('analysis_status')->default('pending');
            $table->json('findings')->nullable();
            $table->text('summary')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_images');
    }
};
