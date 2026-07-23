<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_panel_id')->constrained()->cascadeOnDelete();
            $table->integer('credits_used')->default(0);
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->index('user_id');
            $table->index('test_panel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_submissions');
    }
};
