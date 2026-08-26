<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update existing signup bonus from 3 → 2 (new signup bonus).
        DB::table('settings')
            ->where('key', 'credits.signup_bonus')
            ->update(['value' => '2']);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'credits.signup_bonus')
            ->update(['value' => '3']);
    }
};