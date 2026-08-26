<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('provider_directory_entries')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->comment('1-5 stars');
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index('provider_id');
            $table->unique(['provider_id', 'user_id'], 'provider_reviews_provider_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_reviews');
    }
};