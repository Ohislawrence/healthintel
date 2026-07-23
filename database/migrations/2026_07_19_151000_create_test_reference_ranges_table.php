<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_reference_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_panel_id')->constrained()->cascadeOnDelete();
            $table->string('test_slug', 80);
            $table->string('test_name', 120);
            $table->string('unit', 30);
            $table->integer('decimals')->default(1);
            // Ranges by demographic
            $table->decimal('range_low_male', 12, 4)->nullable();
            $table->decimal('range_high_male', 12, 4)->nullable();
            $table->decimal('range_low_female', 12, 4)->nullable();
            $table->decimal('range_high_female', 12, 4)->nullable();
            // Pregnancy-specific ranges (nullable)
            $table->decimal('range_low_pregnant', 12, 4)->nullable();
            $table->decimal('range_high_pregnant', 12, 4)->nullable();
            // Pediatric ranges (if age < 18)
            $table->decimal('range_low_pediatric', 12, 4)->nullable();
            $table->decimal('range_high_pediatric', 12, 4)->nullable();
            // Critical thresholds
            $table->decimal('critical_low', 12, 4)->nullable();
            $table->decimal('critical_high', 12, 4)->nullable();
            // Data source citation
            $table->string('source', 200)->nullable();
            $table->timestamps();

            $table->index('test_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_reference_ranges');
    }
};
