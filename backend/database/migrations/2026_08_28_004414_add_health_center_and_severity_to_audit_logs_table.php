<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // nullable: hay eventos (ej. super_admin creando una organizacion)
            // donde de verdad no hay un centro de salud al cual asociarlos.
            $table->foreignUuid('health_center_id')
                ->nullable()
                ->after('user_id')
                ->constrained('health_centers')
                ->nullOnDelete();

            // default 'info': todo evento nace como informativo salvo que
            // la logica de negocio (ver AuditLogObserver) determine que es
            // warning o critical.
            $table->string('severity')->default('info')->after('action');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('health_center_id');
            $table->dropColumn('severity');
        });
    }
};