<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_interpretations', function (Blueprint $table) {
            $table->string('classification_status')->nullable()->after('interpretation_version');
            $table->integer('confidence_score')->nullable()->after('classification_status');
            $table->string('escalation_level')->nullable()->after('confidence_score'); // info, flagged, urgent
            $table->text('escalation_message')->nullable()->after('escalation_level');
        });
    }

    public function down(): void
    {
        Schema::table('partner_interpretations', function (Blueprint $table) {
            $table->dropColumn(['classification_status', 'confidence_score', 'escalation_level', 'escalation_message']);
        });
    }
};