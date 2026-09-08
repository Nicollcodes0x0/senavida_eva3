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
        Schema::create('temporary_access_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('health_center_id')->constrained('health_centers');

            $table->string('code_hash');
            $table->enum('status', ['active', 'consumed', 'expired', 'blocked'])->default('active');

            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();

            $table->unsignedTinyInteger('failed_attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temporary_access_codes');
    }
};