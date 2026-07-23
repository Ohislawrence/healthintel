<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix the failed_jobs composite index key length
     * for MySQL/MariaDB versions with 1000-byte index limit.
     */
    public function up(): void
    {
        // Drop the problematic composite index first
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->dropIndex('failed_jobs_connection_queue_failed_at_index');
        });

        // Change the varchar columns from 255 to 191 to fit within key limits
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->string('connection', 191)->change();
            $table->string('queue', 191)->change();
        });

        // Re-create the index with the smaller columns
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->index(['connection', 'queue', 'failed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->dropIndex('failed_jobs_connection_queue_failed_at_index');
        });

        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->string('connection', 255)->change();
            $table->string('queue', 255)->change();
        });

        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->index(['connection', 'queue', 'failed_at']);
        });
    }
};