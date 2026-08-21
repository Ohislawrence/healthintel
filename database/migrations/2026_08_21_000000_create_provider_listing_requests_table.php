<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_listing_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_type', 20)->default('listing')->comment('listing = add facility, promotion = sponsored ad');
            $table->string('facility_name', 255);
            $table->string('type', 30)->default('lab')->comment('lab, hospital, clinic, pharmacy, specialist');
            $table->string('specialty', 200)->nullable();
            $table->string('contact_name', 255);
            $table->string('contact_email', 255);
            $table->string('contact_phone', 30)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('website', 300)->nullable();
            $table->text('description')->nullable();

            // Promotion-specific fields
            $table->string('promotion_plan', 30)->nullable()->comment('sponsored_banner etc.');
            $table->integer('promotion_budget_kobo')->nullable();
            $table->integer('promotion_duration_days')->nullable();

            $table->string('status', 20)->default('pending')->comment('pending, approved, rejected');
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('provider_id')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('request_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_listing_requests');
    }
};