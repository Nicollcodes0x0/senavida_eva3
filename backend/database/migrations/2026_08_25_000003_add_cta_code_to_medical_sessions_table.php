<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_sessions', function (Blueprint $table) {
            $table->string('cta_code', 9)->nullable()->after('temporary_access_code_id');
        });
    }

    public function down(): void
    {
        Schema::table('medical_sessions', function (Blueprint $table) {
            $table->dropColumn('cta_code');
        });
    }
};