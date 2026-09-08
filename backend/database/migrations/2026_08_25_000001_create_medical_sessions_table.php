<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relaciones obligatorias: a quién atendemos y dónde
            $table->foreignUuid('patient_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('health_center_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('unit_id')->constrained()->restrictOnDelete();

            // El CTA que dio origen a esta sesión. unique() = un código nunca
            // abre dos sesiones.
            $table->foreignUuid('temporary_access_code_id')
                ->unique()
                ->constrained('temporary_access_codes')
                ->restrictOnDelete();

            // D-07 resuelto: un solo campo hace de status Y de etapa.
            $table->enum('status', [
                'in_admission',    // "Admisión"
                'in_triage',       // "Categorización"
                'in_medical_care', // "Consulta Médica"
                'closed',
                'cancelled',
                'expired',
            ])->default('in_admission');

            // Datos capturados al abrir la atención (S1)
            $table->text('reason_of_visit');
            $table->jsonb('allergies')->nullable();

            // Ciclo de vida
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();

            // Datos de cierre (S5) — enum cerrado de 3 valores del contrato
            $table->enum('closure_reason', [
                'completed',   // Atención Completada con Éxito
                'referred',    // Derivado a Centro de Alta Complejidad
                'abandoned',   // Abandono voluntario del paciente
            ])->nullable();
            $table->text('summary')->nullable();

            // Quién abrió y quién cerró
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('closed_by')->nullable()->constrained('users')->restrictOnDelete();

            $table->timestamps();
        });

        // RF-027: un paciente no puede tener dos atenciones abiertas a la vez.
        // No es un unique() normal -- solo aplica mientras la sesión sigue viva.
        DB::statement('
            CREATE UNIQUE INDEX medical_sessions_one_open_per_patient
            ON medical_sessions (patient_id)
            WHERE status NOT IN (\'closed\', \'cancelled\', \'expired\')
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_sessions');
    }
};