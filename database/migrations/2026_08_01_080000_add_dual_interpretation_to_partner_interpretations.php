<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_interpretations', function (Blueprint $table) {
            $table->text('clinician_interpretation_text')->nullable()->after('interpretation_text');
            $table->boolean('version_for_patient')->default(true)->after('clinician_interpretation_text');
            $table->string('interpretation_version')->default('v1')->after('version_for_patient');
        });
    }

    public function down(): void
    {
        Schema::table('partner_interpretations', function (Blueprint $table) {
            $table->dropColumn(['clinician_interpretation_text', 'version_for_patient', 'interpretation_version']);
        });
    }
};