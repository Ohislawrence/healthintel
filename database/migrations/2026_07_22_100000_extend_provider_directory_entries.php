<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_directory_entries', function (Blueprint $table) {
            $table->string('type', 30)->after('name')->default('clinic');
            $table->decimal('latitude', 10, 7)->nullable()->after('country');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('partner_status', 20)->default('none')->after('longitude');
            $table->string('referral_link', 300)->nullable()->after('partner_status');
            $table->json('insurance_plans')->nullable()->after('referral_link');
            $table->index('type');
            $table->index('partner_status');
        });
    }

    public function down(): void
    {
        Schema::table('provider_directory_entries', function (Blueprint $table) {
            $table->dropColumn(['type', 'latitude', 'longitude', 'partner_status', 'referral_link', 'insurance_plans']);
        });
    }
};
