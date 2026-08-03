<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->unsignedBigInteger('source_amount_kobo')->comment('The original payment amount in kobo');
            $table->unsignedBigInteger('commission_kobo')->comment('Commission earned in kobo');
            $table->unsignedTinyInteger('percentage_rate')->comment('The percentage rate at time of earning');
            $table->unsignedInteger('payout_number')->default(1)->comment('Which payout this is for this referred user (1,2,3...)');
            $table->string('status', 20)->default('pending')->comment('pending, paid, rejected');
            $table->foreignId('payout_request_id')->nullable()->constrained('referral_payout_requests')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_earnings');
    }
};