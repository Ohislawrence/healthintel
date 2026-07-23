<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_submission_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_submission_id')->constrained()->cascadeOnDelete();
            $table->string('test_slug', 80);
            $table->string('test_name', 120);
            $table->string('unit', 30);
            $table->decimal('value', 12, 4);
            // Flag result from the reference-range engine
            $table->string('flag', 20)->nullable()->comment('normal, low, high, critical_low, critical_high');
            $table->timestamps();

            $table->index('test_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_submission_values');
    }
};
