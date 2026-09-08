<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_sessions', function (Blueprint $table) {
            $table->boolean('triage_skipped')->default(false)->after('status');
            $table->text('triage_skip_reason')->nullable()->after('triage_skipped');
            $table->foreignUuid('triage_skipped_by')->nullable()->after('triage_skip_reason')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('medical_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('triage_skipped_by');
            $table->dropColumn(['triage_skipped', 'triage_skip_reason']);
        });
    }
};