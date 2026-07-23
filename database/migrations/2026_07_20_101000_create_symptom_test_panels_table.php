<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('symptom_test_panels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('symptom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_panel_id')->constrained()->cascadeOnDelete();
            $table->integer('relevance_score')->default(1); // 1-10, higher = more relevant
            $table->timestamps();

            $table->unique(['symptom_id', 'test_panel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('symptom_test_panels');
    }
};
