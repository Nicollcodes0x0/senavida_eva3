<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('medical_session_id')->constrained('medical_sessions');
            $table->string('sender_type');
            $table->foreignUuid('sender_id')->nullable()->constrained('users');
            $table->string('sender_name');
            $table->string('message_type');
            $table->text('body');
            $table->string('origin');
            $table->string('status')->default('sent');
            $table->timestamp('sent_at');
            $table->timestamp('confirmed_by_patient_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->foreignUuid('pictogram_id')->nullable()->constrained('pictograms');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};