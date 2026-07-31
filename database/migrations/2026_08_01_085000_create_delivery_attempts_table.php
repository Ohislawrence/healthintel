<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interpretation_id')->constrained('partner_interpretations')->cascadeOnDelete();
            $table->string('delivery_method'); // email, whatsapp, sms
            $table->string('recipient');
            $table->string('message_id')->nullable(); // external provider ID
            $table->string('status')->default('queued'); // queued, sent, delivered, failed, bounced
            $table->json('provider_response')->nullable(); // raw API response
            $table->text('error_message')->nullable();
            $table->tinyInteger('attempt_number')->default(1);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index(['interpretation_id', 'status']);
            $table->index(['status', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_attempts');
    }
};