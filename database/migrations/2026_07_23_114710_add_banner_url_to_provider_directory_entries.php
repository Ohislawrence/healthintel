<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_directory_entries', function (Blueprint $table) {
            $table->string('banner_url', 500)->nullable()->after('monetization_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('provider_directory_entries', function (Blueprint $table) {
            $table->dropColumn('banner_url');
        });
    }
};