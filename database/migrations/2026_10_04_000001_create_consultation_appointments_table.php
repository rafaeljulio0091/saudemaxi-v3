<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_appointments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('request_id');
            $table->string('provider', 40)->default('lsxmedical');
            $table->string('provider_consultation_id', 100)->nullable();
            $table->string('consultation_code', 100)->nullable();
            $table->unsignedBigInteger('specialty_id');
            $table->text('specialty_name');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->text('doctor_name')->nullable();
            $table->boolean('is_real_doctor');
            $table->dateTimeTz('scheduled_for');
            $table->string('provider_status', 40)->nullable();
            $table->string('sync_status', 40);
            $table->boolean('is_paid')->default(false);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('last_error_reason', 40)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'request_id'], 'consultation_request_unique');
            $table->unique(['tenant_id', 'provider', 'consultation_code'], 'consultation_provider_code_unique');
            $table->index(['tenant_id', 'user_id', 'scheduled_for'], 'consultation_patient_schedule_idx');
            $table->index(['tenant_id', 'sync_status', 'updated_at'], 'consultation_sync_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_appointments');
    }
};
