<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interpretation_panels', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();             // 'fbc', 'lft', 'lipid', 'rft', 'tft'
            $table->string('name');                       // 'Full Blood Count', 'Liver Function Test'
            $table->text('description')->nullable();
            $table->json('test_codes');                   // ['haemoglobin','wbc','rbc','platelets','pcv','mcv','mch','mchc']
            $table->json('layout_sections')->nullable();  // [{ "title": "Red Cell Indices", "tests": ["haemoglobin","pcv","rbc","mcv","mch","mchc"] }, ...]
            $table->string('status')->default('draft');   // draft, approved, deprecated
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->index(['status', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interpretation_panels');
    }
};