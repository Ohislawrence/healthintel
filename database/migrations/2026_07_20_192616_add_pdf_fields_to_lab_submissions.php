<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_submissions', function (Blueprint $table) {
            $table->string('submission_type')->default('manual')->after('user_id');
            $table->string('pdf_report_url')->nullable()->after('credits_used');
            $table->text('pdf_text')->nullable()->after('pdf_report_url');
            $table->foreignId('test_panel_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lab_submissions', function (Blueprint $table) {
            $table->dropColumn(['submission_type', 'pdf_report_url', 'pdf_text']);
            $table->foreignId('test_panel_id')->nullable(false)->change();
        });
    }
};