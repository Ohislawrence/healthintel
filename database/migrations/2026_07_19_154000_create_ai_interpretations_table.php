 <?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_interpretations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_submission_id')->constrained()->cascadeOnDelete();
            $table->string('model_used', 80)->nullable();
            // Full prompt sent to the LLM
            $table->text('prompt_input');
            // Raw LLM output (JSONB-capable)
            $table->json('llm_output')->nullable();
            // Parsed interpretation text
            $table->text('interpretation_text')->nullable();
            // Guardrail warnings
            $table->json('guardrail_flags')->nullable();
            // Status
            $table->string('status', 30)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index('lab_submission_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_interpretations');
    }
};
