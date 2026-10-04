<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->morphs('addressable');
            $table->text('zip_code')->nullable();
            $table->text('street');
            $table->text('number')->nullable();
            $table->text('complement')->nullable();
            $table->text('district')->nullable();
            $table->string('city', 150);
            $table->char('state', 2);
            $table->char('country', 2)->default('BR');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'city', 'state'], 'addr_tenant_city_state_idx');
        });

        Schema::create('health_professional_health_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('health_professional_id')->constrained()->cascadeOnDelete();
            $table->foreignId('health_unit_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->timestamps();

            $table->unique(['health_professional_id', 'health_unit_id'], 'professional_health_unit_unique');
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('health_unit_patient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('health_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->timestamps();

            $table->unique(['health_unit_id', 'patient_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('external_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->morphs('external_identifiable', 'ext_identity_identifiable_idx');
            $table->string('provider', 60);
            $table->string('external_id', 191);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'provider', 'external_id'], 'external_identity_provider_unique');
            $table->unique(['external_identifiable_type', 'external_identifiable_id', 'provider'], 'external_identity_resource_unique');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('resource_type', 100);
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->uuid('resource_uuid')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'action', 'created_at']);
            $table->index(['tenant_id', 'resource_type', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('external_identities');
        Schema::dropIfExists('health_unit_patient');
        Schema::dropIfExists('health_professional_health_unit');
        Schema::dropIfExists('addresses');
    }
};
