<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('symptom_check_funnels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('symptoms_selected')->nullable();        // ["headache", "fever"]
            $table->json('panels_suggested')->nullable();          // [{slug, name, relevance}, ...]
            $table->json('panels_clicked')->nullable();            // which panels the user clicked
            $table->foreignId('provider_viewed_id')->nullable()->constrained('provider_directory_entries')->nullOnDelete();
            $table->boolean('appointment_booked')->default(false);
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->string('stage')->default('checked'); // checked, panel_viewed, provider_viewed, booked
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('symptom_check_funnels');
    }
};