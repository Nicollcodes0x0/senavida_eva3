<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pictograms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pictogram_category_id')->constrained('pictogram_categories');
            $table->string('title', 100);
            $table->string('phrase', 255);
            $table->string('speech_text', 255);
            $table->string('emoji', 8);
            $table->string('severity')->default('neutral');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pictograms');
    }
};