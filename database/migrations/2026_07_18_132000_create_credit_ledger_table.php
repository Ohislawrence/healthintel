<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action_type', 50);
            $table->integer('credits_delta');
            $table->integer('balance_after');
            $table->nullableMorphs('reference');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('action_type');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_ledger');
    }
};
