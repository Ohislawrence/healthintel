<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benchmark_runs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('dataset_version')->default('v1');
            $table->integer('total_questions')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->decimal('avg_response_time_ms', 10, 2)->nullable();
            $table->string('model_used')->nullable();
            $table->json('specialty_breakdown')->nullable(); // { "hematology": { "total": 20, "correct": 18 }, ... }
            $table->json('difficulty_breakdown')->nullable(); // { "easy": { "total": 30, "correct": 28 }, ... }
            $table->json('detailed_results')->nullable(); // [{ "question_id": 1, "correct": true, "response": "...", "expected": "..." }]
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benchmark_runs');
    }
};