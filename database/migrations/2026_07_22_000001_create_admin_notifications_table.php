<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('body');
            $table->string('target', 20)->default('all'); // all, users, user_ids
            $table->json('user_ids')->nullable(); // specific user IDs for targeted push
            $table->json('read_by')->nullable(); // user IDs who have read the notification
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};