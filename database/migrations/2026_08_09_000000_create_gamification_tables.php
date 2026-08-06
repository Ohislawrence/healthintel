<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('badge_key');
            $table->string('badge_name');
            $table->string('badge_description')->nullable();
            $table->timestamp('awarded_at')->useCurrent();
            $table->timestamps();
            $table->unique(['user_id', 'badge_key']);
        });

        Schema::create('user_streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('streak_type');
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'streak_type']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('user_streaks');
        Schema::dropIfExists('user_badges');
    }
};