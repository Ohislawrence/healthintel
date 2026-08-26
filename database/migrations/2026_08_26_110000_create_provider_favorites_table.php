<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('provider_directory_entries')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'provider_id'], 'provider_favorites_user_provider_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_favorites');
    }
};