<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partnership_id')->constrained('lab_partnerships')->cascadeOnDelete();
            $table->string('invoice_number')->unique(); // e.g., INV-2026-001
            $table->string('period_start'); // e.g., 2026-07-01
            $table->string('period_end');
            $table->integer('total_interpretations')->default(0);
            $table->integer('included_in_allowance')->default(0);
            $table->integer('billable_interpretations')->default(0);
            $table->integer('unit_price_kobo')->default(0); // price per billable interpretation
            $table->integer('subtotal_kobo')->default(0);
            $table->integer('discount_kobo')->default(0);
            $table->integer('total_kobo')->default(0);
            $table->json('line_items')->nullable(); // breakdown per test type
            $table->string('status')->default('pending'); // pending, sent, paid, overdue, cancelled
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['partnership_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_invoices');
    }
};