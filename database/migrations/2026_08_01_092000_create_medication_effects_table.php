<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_effects', function (Blueprint $table) {
            $table->id();
            $table->string('medication_slug');          // 'metformin', 'lisinopril', 'atorvastatin'
            $table->string('medication_name');          // 'Metformin', 'Lisinopril'
            $table->string('test_code');                // matches reference_ranges.test_code
            $table->string('expected_effect')->default('no_effect'); // elevates, lowers, no_effect, variable
            $table->string('severity')->default('mild'); // mild, moderate, significant
            $table->text('clinician_note')->nullable(); // context for the interpretation
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['medication_slug', 'test_code'], 'med_eff_unique');
            $table->index(['test_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_effects');
    }
};