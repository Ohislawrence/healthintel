<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
            $table->string('author_role')->nullable(); // e.g., "Patient", "Lab Director", "Clinician"
            $table->string('author_organization')->nullable();
            $table->string('author_avatar_url')->nullable();
            $table->text('quote');
            $table->integer('rating')->nullable(); // 1-5 stars
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('category')->nullable(); // patient, clinician, lab, insurance
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};