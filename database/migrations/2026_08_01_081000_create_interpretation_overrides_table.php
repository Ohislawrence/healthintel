<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interpretation_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interpretation_id')->constrained('partner_interpretations')->cascadeOnDelete();
            $table->foreignId('overridden_by')->nullable()->constrained('provider_directory_entries')->nullOnDelete();

            // Snapshot — original state before override
            $table->text('original_clinician_text')->nullable();
            $table->text('original_patient_text')->nullable();
            $table->string('original_status')->nullable();

            // Snapshot — new state after override
            $table->text('new_clinician_text')->nullable();
            $table->text('new_patient_text')->nullable();
            $table->string('new_status')->nullable();

            // Metadata
            $table->string('override_type')->default('edit'); // edit, suppress, reclassify, toggle_version
            $table->text('override_reason'); // required — why this change was made
            $table->string('changed_fields')->nullable(); // comma-separated list: "patient_text,clinician_text,version_for_patient"

            $table->timestamps();

            $table->index(['interpretation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interpretation_overrides');
    }
};