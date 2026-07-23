<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('provider_directory_entries')->nullOnDelete();
            $table->string('source_feature', 50)->default('directory');
            $table->string('action', 30)->default('click');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('source_feature');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_events');
    }
};
