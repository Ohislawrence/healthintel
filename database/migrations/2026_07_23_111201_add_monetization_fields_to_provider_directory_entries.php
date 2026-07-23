<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_directory_entries', function (Blueprint $table) {
            $table->string('monetization_type', 20)->nullable()->after('partner_status')->comment('affiliate, sponsored');
            $table->integer('monetization_rate')->nullable()->after('monetization_type')->comment('affiliate: naira per conversion');
            $table->integer('monetization_amount')->nullable()->after('monetization_rate')->comment('sponsored: total naira paid');
            $table->string('monetization_limit_type', 10)->nullable()->after('monetization_amount')->comment('time, views');
            $table->integer('monetization_limit_value')->nullable()->after('monetization_limit_type')->comment('days or max views');
            $table->integer('monetization_views_used')->nullable()->default(0)->after('monetization_limit_value');
            $table->timestamp('monetization_started_at')->nullable()->after('monetization_views_used');
            $table->timestamp('monetization_expires_at')->nullable()->after('monetization_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('provider_directory_entries', function (Blueprint $table) {
            $table->dropColumn([
                'monetization_type', 'monetization_rate', 'monetization_amount',
                'monetization_limit_type', 'monetization_limit_value', 'monetization_views_used',
                'monetization_started_at', 'monetization_expires_at',
            ]);
        });
    }
};