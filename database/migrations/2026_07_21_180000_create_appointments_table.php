<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('provider_directory_entries')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->string('status')->default('upcoming'); // upcoming, completed, cancelled
            $table->text('notes')->nullable();
            $table->boolean('reminder_enabled')->default(true);
            $table->integer('reminder_minutes_before')->default(30);
            $table->timestamps();

            $table->index(['user_id', 'appointment_date']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};