<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('codigo')->unique();
            $table->string('especialidade');
            $table->string('medico')->nullable();
            $table->string('status');
            $table->dateTime('agendada_para');
            $table->boolean('pago')->default(false);
            $table->string('duracao')->nullable();
            $table->string('request_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
