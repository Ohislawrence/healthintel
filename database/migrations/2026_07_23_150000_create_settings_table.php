<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, decimal, json
            $table->string('group')->default('general'); // general, credits, api, features
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default settings
        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }

    private function seedDefaults(): void
    {
        $defaults = [
            // Credits
            ['key' => 'credits.signup_bonus', 'value' => '3', 'type' => 'integer', 'group' => 'credits', 'label' => 'Signup Bonus Credits', 'description' => 'Number of free credits given to new users upon registration'],
            ['key' => 'credits.lab_interpretation', 'value' => '2', 'type' => 'integer', 'group' => 'credits', 'label' => 'Manual Lab Interpretation Cost', 'description' => 'Credits required to submit a lab panel manually'],
            ['key' => 'credits.pdf_interpretation', 'value' => '3', 'type' => 'integer', 'group' => 'credits', 'label' => 'PDF Upload Interpretation Cost', 'description' => 'Credits required to upload and interpret a PDF lab report'],
            ['key' => 'credits.symptom_check', 'value' => '1', 'type' => 'integer', 'group' => 'credits', 'label' => 'Symptom Check Cost', 'description' => 'Credits required for AI-powered symptom checking'],

            // API
            ['key' => 'api.deepseek_key', 'value' => '', 'type' => 'string', 'group' => 'api', 'label' => 'DeepSeek API Key', 'description' => 'API key for the DeepSeek AI interpretation service'],
            ['key' => 'api.deepseek_model', 'value' => 'deepseek-chat', 'type' => 'string', 'group' => 'api', 'label' => 'DeepSeek Model', 'description' => 'Model identifier for DeepSeek API calls'],
            ['key' => 'api.deepseek_max_tokens', 'value' => '4096', 'type' => 'integer', 'group' => 'api', 'label' => 'DeepSeek Max Tokens', 'description' => 'Maximum tokens per AI response'],
            ['key' => 'api.deepseek_temperature', 'value' => '0.3', 'type' => 'decimal', 'group' => 'api', 'label' => 'DeepSeek Temperature', 'description' => 'Temperature setting for AI generation (0-1)'],
            ['key' => 'api.deepseek_daily_limit', 'value' => '20', 'type' => 'integer', 'group' => 'api', 'label' => 'Daily API Limit Per User', 'description' => 'Maximum DeepSeek API calls per user per day'],
            ['key' => 'api.paystack_secret', 'value' => '', 'type' => 'string', 'group' => 'api', 'label' => 'Paystack Secret Key', 'description' => 'Paystack API secret key for payment processing'],
            ['key' => 'api.paystack_public', 'value' => '', 'type' => 'string', 'group' => 'api', 'label' => 'Paystack Public Key', 'description' => 'Paystack API public key'],

            // App
            ['key' => 'app.name', 'value' => 'HealthIntel', 'type' => 'string', 'group' => 'general', 'label' => 'Application Name', 'description' => 'Public-facing name of the application'],
            ['key' => 'app.maintenance_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'general', 'label' => 'Maintenance Mode', 'description' => 'Enable maintenance mode to block public access'],
        ];

        $now = now();
        $rows = array_map(fn($d) => array_merge($d, ['created_at' => $now, 'updated_at' => $now]), $defaults);

        DB::table('settings')->insert($rows);
    }
};