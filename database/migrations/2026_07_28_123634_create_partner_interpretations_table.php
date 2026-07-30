<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_interpretations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partnership_id')->constrained('lab_partnerships')->cascadeOnDelete();
            $table->foreignId('lab_submission_id')->nullable()->constrained('lab_submissions')->nullOnDelete();
            $table->string('patient_identifier')->nullable(); // lab's internal patient ID or barcode
            $table->string('test_name'); // e.g., "Haemoglobin", "WBC", "Glucose"
            $table->string('value');
            $table->string('unit')->nullable();
            $table->string('reference_range_low')->nullable();
            $table->string('reference_range_high')->nullable();
            $table->string('sex')->nullable(); // patient demographic for reference range context
            $table->string('age')->nullable();
            $table->text('interpretation_text')->nullable(); // AI-generated plain-language interpretation
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->string('delivery_method')->nullable(); // email, whatsapp, sms, pdf
            $table->string('delivery_status')->nullable(); // sent, delivered, failed
            $table->integer('cost_to_partner')->default(0); // in kobo
            $table->string('external_id')->nullable(); // batch ID or external reference
            $table->timestamps();
            $table->index(['partnership_id', 'status']);
            $table->index('external_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_interpretations');
    }
};