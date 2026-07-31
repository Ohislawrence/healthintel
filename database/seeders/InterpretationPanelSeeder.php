<?php

namespace Database\Seeders;

use App\Models\InterpretationPanel;
use App\Models\PanelNarrativeTemplate;
use Illuminate\Database\Seeder;

class InterpretationPanelSeeder extends Seeder
{
    public function run(): void
    {
        $panels = [
            [
                'slug' => 'fbc',
                'name' => 'Full Blood Count (FBC)',
                'description' => 'Complete haematological assessment including red cell indices, white cell and platelet counts.',
                'test_codes' => ['haemoglobin', 'pcv', 'rbc', 'wbc', 'platelets', 'mcv', 'mch', 'mchc', 'rdw', 'neutrophils', 'lymphocytes'],
                'layout_sections' => [
                    ['title' => 'Red Cell Indices', 'tests' => ['haemoglobin', 'pcv', 'rbc', 'mcv', 'mch', 'mchc', 'rdw']],
                    ['title' => 'White Cells & Platelets', 'tests' => ['wbc', 'neutrophils', 'lymphocytes', 'platelets']],
                ],
            ],
            [
                'slug' => 'lft',
                'name' => 'Liver Function Test (LFT)',
                'description' => 'Assessment of hepatocellular integrity, synthetic function and cholestasis.',
                'test_codes' => ['alt', 'ast', 'alp', 'ggt', 'total_bilirubin', 'direct_bilirubin', 'total_protein', 'albumin'],
                'layout_sections' => [
                    ['title' => 'Hepatocellular Markers', 'tests' => ['alt', 'ast']],
                    ['title' => 'Cholestatic Markers', 'tests' => ['alp', 'ggt', 'total_bilirubin', 'direct_bilirubin']],
                    ['title' => 'Synthetic Function', 'tests' => ['total_protein', 'albumin']],
                ],
            ],
            [
                'slug' => 'lipid',
                'name' => 'Lipid Profile',
                'description' => 'Cardiovascular risk assessment panel.',
                'test_codes' => ['total_cholesterol', 'ldl', 'hdl', 'triglycerides'],
                'layout_sections' => [
                    ['title' => 'Lipid Panel', 'tests' => ['total_cholesterol', 'ldl', 'hdl', 'triglycerides']],
                ],
            ],
            [
                'slug' => 'rft',
                'name' => 'Renal Function Test (RFT)',
                'description' => 'Kidney function and electrolyte assessment.',
                'test_codes' => ['creatinine', 'urea', 'sodium', 'potassium', 'chloride', 'bicarbonate', 'uric_acid'],
                'layout_sections' => [
                    ['title' => 'Renal Markers', 'tests' => ['creatinine', 'urea', 'uric_acid']],
                    ['title' => 'Electrolytes', 'tests' => ['sodium', 'potassium', 'chloride', 'bicarbonate']],
                ],
            ],
            [
                'slug' => 'tft',
                'name' => 'Thyroid Function Test (TFT)',
                'description' => 'Thyroid hormone assessment.',
                'test_codes' => ['tsh', 'ft3', 'ft4'],
            ],
        ];

        foreach ($panels as $data) {
            InterpretationPanel::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['status' => 'approved']),
            );
        }

        $this->command?->info('Seeded ' . count($panels) . ' interpretation panels.');
    }
}