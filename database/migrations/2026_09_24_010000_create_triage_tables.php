<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triage_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status', 'started_at']);
            $table->index(['tenant_id', 'patient_id', 'status']);
        });

        Schema::create('triage_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('triage_session_id')->constrained('triage_sessions')->cascadeOnDelete();
            $table->string('sender', 24);
            $table->unsignedInteger('sequence');
            $table->uuid('request_id')->nullable();
            $table->longText('content');
            $table->timestamps();
            $table->unique(['triage_session_id', 'sequence']);
            $table->unique(['triage_session_id', 'request_id']);
            $table->index(['tenant_id', 'triage_session_id', 'created_at'], 'triage_messages_tenant_session_created_index');
        });

        Schema::create('triage_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('triage_session_id')->constrained('triage_sessions')->cascadeOnDelete();
            $table->foreignId('triage_message_id')->nullable()->constrained('triage_messages')->nullOnDelete();
            $table->string('classification', 32);
            $table->decimal('confidence', 5, 4)->nullable();
            $table->longText('structured_state');
            $table->boolean('requires_human_review')->default(false);
            $table->string('source', 32);
            $table->string('provider_model')->nullable();
            $table->string('safety_rule_version', 64);
            $table->timestamps();
            $table->index(['tenant_id', 'classification', 'requires_human_review'], 'triage_assessments_routing_index');
            $table->index(['triage_session_id', 'created_at']);
        });

        Schema::create('triage_ai_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('triage_session_id')->constrained('triage_sessions')->cascadeOnDelete();
            $table->uuid('correlation_id');
            $table->string('provider', 32);
            $table->string('model')->nullable();
            $table->string('operation', 64);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('successful');
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->string('classification', 32)->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->boolean('fallback_used')->default(false);
            $table->string('safety_rule_version', 64);
            $table->string('error_code', 64)->nullable();
            $table->timestamps();
            $table->unique('correlation_id');
            $table->index(['tenant_id', 'provider', 'successful', 'created_at'], 'triage_ai_events_metrics_index');
            $table->index(['triage_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triage_ai_events');
        Schema::dropIfExists('triage_assessments');
        Schema::dropIfExists('triage_messages');
        Schema::dropIfExists('triage_sessions');
    }
};
