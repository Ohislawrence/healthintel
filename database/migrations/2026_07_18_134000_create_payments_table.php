<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('purchasable');
            $table->string('provider', 30)->comment('paystack, flutterwave');
            $table->string('reference', 100)->unique();
            $table->string('provider_reference', 100)->nullable();
            $table->integer('amount_kobo');
            $table->string('currency', 3)->default('NGN');
            $table->string('status', 20)->default('pending');
            $table->json('provider_response')->nullable();
            $table->json('webhook_log')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
