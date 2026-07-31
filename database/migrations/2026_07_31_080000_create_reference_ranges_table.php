<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('test_code');                 // e.g., 'haemoglobin', 'alt', 'wbc' (not unique — multiple demographics rows per test)
            $table->string('test_name');                  // e.g., "Haemoglobin", "ALT (SGPT)"
            $table->string('category');                   // haematology, chemistry, lipids, thyroid, urinalysis, serology, hormones
            $table->string('sex')->default('all');        // male, female, all
            $table->decimal('age_min_years', 5, 1)->nullable(); // inclusive
            $table->decimal('age_max_years', 5, 1)->nullable(); // inclusive
            $table->boolean('pregnancy_applicable')->default(false);
            $table->tinyInteger('pregnancy_trimester')->nullable(); // 1, 2, 3, null = any or not applicable
            $table->decimal('range_low', 12, 4);          // normal low
            $table->decimal('range_high', 12, 4);         // normal high
            $table->decimal('critical_low', 12, 4)->nullable();
            $table->decimal('critical_high', 12, 4)->nullable();
            $table->string('unit');                       // canonical unit
            $table->text('source')->nullable();           // WHO, NCCLS, Nigerian FMOH, etc.
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Composite unique: one row per test_code + demographic combination
            $table->unique(
                ['test_code', 'sex', 'age_min_years', 'age_max_years', 'pregnancy_applicable', 'pregnancy_trimester'],
                'rr_test_demo_unique'
            );
            // Index for fast lookups by demographics
            $table->index(['test_code', 'sex', 'age_min_years', 'age_max_years', 'is_active'], 'rr_demographics_idx');
            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_ranges');
    }
};