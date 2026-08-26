<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Captured at booking time for the provider's reference.
            $table->string('patient_name')->nullable()->after('provider_id');
            $table->string('patient_phone', 30)->nullable()->after('patient_name');
            $table->string('status')->default('upcoming')->comment('upcoming,pending,confirmed,declined,completed,cancelled')->change();
            $table->text('provider_notes')->nullable()->after('notes');
            $table->timestamp('confirmed_at')->nullable()->after('provider_notes');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['patient_name', 'patient_phone', 'provider_notes', 'confirmed_at']);
            $table->string('status')->default('upcoming')->comment('upcoming, completed, cancelled')->change();
        });
    }
};