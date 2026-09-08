<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // unique: garantiza en la base de datos que solo puede existir
            // UN registro de configuracion por centro de salud (decision D-14).
            $table->foreignUuid('health_center_id')
                ->unique()
                ->constrained('health_centers')
                ->cascadeOnDelete();

            // Cuantos intentos fallidos de CTA se permiten antes de bloquear
            // el codigo. Este valor solo afecta a los CTA generados DESPUES
            // del cambio: los ya emitidos guardan su propio limite (ver
            // TemporaryAccessCode.max_attempts), preservando la regla con
            // la que nacieron.
            $table->unsignedTinyInteger('cta_max_attempts')->default(3);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_settings');
    }
};