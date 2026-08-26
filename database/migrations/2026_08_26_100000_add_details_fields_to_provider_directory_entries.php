<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_directory_entries', function (Blueprint $table) {
            $table->string('whatsapp', 30)->nullable()->after('phone');
            $table->json('services')->nullable()->after('bio');
            $table->json('opening_hours')->nullable()->after('website');
            $table->json('gallery')->nullable()->after('logo_url');
        });
    }

    public function down(): void
    {
        Schema::table('provider_directory_entries', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'services', 'opening_hours', 'gallery']);
        });
    }
};