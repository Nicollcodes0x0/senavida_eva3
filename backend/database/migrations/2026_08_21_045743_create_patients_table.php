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
        Schema::create('patients', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Identificación
            $table->string('name');
            $table->string('national_id')->unique();
            $table->enum('national_id_type', ['rut', 'pasaporte']);
            $table->date('birth_date');

            // Datos clínicos y de contacto
            $table->string('health_insurance')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('primary_health_center')->nullable();
            $table->text('allergies')->nullable();
            $table->text('health_conditions')->nullable();
            $table->enum('communication_preference', ['senas', 'texto', 'lectura_labial', 'mixto']);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};