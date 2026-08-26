<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_reports', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->default('error'); // error, warning, info
            $table->string('source', 30)->default('frontend'); // frontend, api, server
            $table->string('type', 255)->nullable(); // exception class / error type
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('url', 1000)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('occurrences')->default(1);
            $table->timestamp('last_seen_at')->nullable();
            $table->string('status', 20)->default('open'); // open, resolved, ignored
            $table->timestamps();

            $table->index('status');
            $table->index('source');
            $table->index('level');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_reports');
    }
};