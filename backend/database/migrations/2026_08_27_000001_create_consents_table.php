<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('medical_session_id')->constrained('medical_sessions');
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->foreignUuid('requested_by')->constrained('users');
            $table->string('consent_type');
            $table->foreignUuid('patient_contact_id')->nullable()->constrained('patient_contacts');
            $table->string('status')->default('pending');
            $table->timestamp('requested_at');
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->jsonb('evidence')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};