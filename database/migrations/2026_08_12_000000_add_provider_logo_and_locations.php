<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Brand logo for the provider listing.
        Schema::table('provider_directory_entries', function (Blueprint $table) {
            $table->string('logo_url', 500)->nullable()->after('banner_url');
        });

        // Multiple physical locations for a single provider/business.
        Schema::create('provider_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('provider_directory_entries')->cascadeOnDelete();
            $table->string('name', 200)->nullable(); // e.g. "Aro Lab - Ikeja Branch"
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable()->default('Nigeria');
            $table->string('phone', 30)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index('city');
            $table->index('state');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_locations');

        Schema::table('provider_directory_entries', function (Blueprint $table) {
            $table->dropColumn('logo_url');
        });
    }
};