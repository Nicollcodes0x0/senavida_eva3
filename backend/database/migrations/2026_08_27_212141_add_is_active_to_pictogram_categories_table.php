<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pictogram_categories', function (Blueprint $table) {
            // default(true): las categorias que ya existen quedan activas
            // sin necesidad de actualizarlas manualmente una por una.
            $table->boolean('is_active')->default(true)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('pictogram_categories', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};