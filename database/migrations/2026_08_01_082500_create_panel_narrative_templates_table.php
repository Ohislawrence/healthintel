<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panel_narrative_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained('interpretation_panels')->cascadeOnDelete();
            $table->string('test_code');                       // matches reference_ranges.test_code
            $table->string('result_category');                 // normal, abnormal_low, abnormal_high, critical_low, critical_high
            $table->text('clinician_template')->nullable();    // {value} {unit} {range_low} {range_high} placeholders
            $table->text('patient_template')->nullable();      // plain-language version
            $table->string('escalation_level')->default('info'); // info, flagged, urgent
            $table->text('required_action_text')->nullable();  // e.g. "Speak to a doctor within 24 hours"
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->unique(['panel_id', 'test_code', 'result_category'], 'pnl_tmpl_unique');
            $table->index(['panel_id', 'result_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panel_narrative_templates');
    }
};