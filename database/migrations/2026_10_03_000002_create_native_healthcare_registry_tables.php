<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->char('state', 2);
            $table->string('ibge_code', 7)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'ibge_code']);
            $table->index(['tenant_id', 'state', 'name']);
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->text('cnpj')->nullable();
            $table->char('cnpj_hash', 64)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'cnpj_hash']);
            $table->index(['tenant_id', 'type', 'status']);
        });

        Schema::create('health_units', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 80)->nullable();
            $table->string('type', 60);
            $table->text('phone')->nullable();
            $table->text('email')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_id', 'code']);
            $table->index(['tenant_id', 'municipality_id', 'status']);
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('holder_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('name');
            $table->string('social_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('sex', 30)->nullable();
            $table->text('cpf');
            $table->char('cpf_hash', 64);
            $table->text('cns')->nullable();
            $table->char('cns_hash', 64)->nullable();
            $table->text('email')->nullable();
            $table->char('email_hash', 64)->nullable();
            $table->text('phone')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'cpf_hash']);
            $table->unique(['tenant_id', 'cns_hash']);
            $table->unique(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'status', 'name']);
            $table->index(['tenant_id', 'municipality_id']);
            $table->index(['tenant_id', 'email_hash']);
        });

        Schema::create('health_professionals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('professional_type', 60);
            $table->string('registration_number', 80);
            $table->char('registration_state', 2)->nullable();
            $table->string('registration_authority', 30);
            $table->string('specialty')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'registration_authority', 'registration_state', 'registration_number'], 'health_professional_registration_unique');
            $table->unique(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'professional_type', 'status']);
        });

        Schema::create('pharmacies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('corporate_name')->nullable();
            $table->text('cnpj')->nullable();
            $table->char('cnpj_hash', 64)->nullable();
            $table->text('phone')->nullable();
            $table->text('email')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('data_source', 40)->default('manual');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'cnpj_hash']);
            $table->index(['tenant_id', 'municipality_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacies');
        Schema::dropIfExists('health_professionals');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('health_units');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('municipalities');
    }
};
