<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_directory_entries', function (Blueprint $table) {
            $table->string('access_code', 64)->nullable()->unique()->after('referral_link');
            $table->timestamp('access_code_generated_at')->nullable()->after('access_code');
        });
    }

    public function down(): void
    {
        Schema::table('provider_directory_entries', function (Blueprint $table) {
            $table->dropColumn(['access_code', 'access_code_generated_at']);
        });
    }
};