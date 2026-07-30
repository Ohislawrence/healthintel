<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_partnerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('provider_directory_entries')->cascadeOnDelete();
            $table->enum('plan_tier', ['pilot', 'standard', 'premium'])->default('pilot');
            $table->enum('pricing_model', ['per_report', 'volume_tier', 'flat_monthly'])->default('per_report');
            $table->integer('rate_per_report')->nullable(); // in kobo
            $table->integer('monthly_allowance')->nullable();
            $table->integer('overage_rate')->nullable();
            $table->boolean('white_label')->default(false);
            $table->string('brand_logo_url')->nullable();
            $table->string('brand_primary_color')->nullable()->default('#0E6B5C');
            $table->text('brand_contact_info')->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->string('status')->default('active'); // active, pilot, expired, cancelled
            $table->boolean('ndpa_agreement_signed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_partnerships');
    }
};