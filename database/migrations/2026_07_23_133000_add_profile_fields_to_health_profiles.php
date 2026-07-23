<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_profiles', function (Blueprint $table) {
            $table->decimal('height_cm', 5, 1)->nullable()->after('is_pregnant');
            $table->decimal('weight_kg', 5, 1)->nullable()->after('height_cm');
            $table->string('blood_type', 15)->nullable()->after('weight_kg');
            $table->text('medical_conditions')->nullable()->after('blood_type');
            $table->text('current_medications')->nullable()->after('medical_conditions');
        });
    }

    public function down(): void
    {
        Schema::table('health_profiles', function (Blueprint $table) {
            $table->dropColumn(['height_cm', 'weight_kg', 'blood_type', 'medical_conditions', 'current_medications']);
        });
    }
};