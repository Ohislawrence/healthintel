<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('endpoint', 768);
            $table->string('p256dh', 512)->nullable();
            $table->string('auth', 256)->nullable();
            $table->string('content_encoding', 32)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('device_type', 32)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('endpoint');
            $table->index('user_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};