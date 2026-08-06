<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role'); // 'user' or 'assistant'
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_messages');
    }
};