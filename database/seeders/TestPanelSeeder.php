<?php

namespace Database\Seeders;

use App\Models\TestPanel;
use App\Models\TestReferenceRange;
use Illuminate\Database\Seeder;

class TestPanelSeeder extends Seeder
{
    private array $panels = [
        [
            'name' => 'Full Blood Count (FBC)',
            'slug' => 'full-blood-count',
            'description' => 'Measures red blood cells, white blood cells, haemoglobin, and platelets to assess overall health and detect disorders like anaemia or infection.',
            'tests' => [
                ['name' => 'Haemoglobin (Hb)', 'slug' => 'haemoglobin', 'unit' => 'g/dL', 'decimals' => 1],
                ['name' => 'White Blood Cell Count (WBC)', 'slug' => 'wbc', 'unit' => '×10⁹/L', 'decimals' => 1],
                ['name' => 'Red Blood Cell Count (RBC)', 'slug' => 'rbc', 'unit' => '×10¹²/L', 'decimals' => 2],
                ['name' => 'Haematocrit (HCT)', 'slug' => 'hct', 'unit' => '%', 'decimals' => 1],
                ['name' => 'Mean Corpuscular Volume (MCV)', 'slug' => 'mcv', 'unit' => 'fL', 'decimals' => 1],
                ['name' => 'Platelet Count', 'slug' => 'platelets', 'unit' => '×10⁹/L', 'decimals' => 0],
            ],
            'sort_order' => 1,
        ],
        [
            'name' => 'Lipid Profile',
            'slug' => 'lipid-profile',
            'description' => 'Measures cholesterol and triglyceride levels to assess cardiovascular disease risk.',
            'tests' => [
                ['name' => 'Total Cholesterol', 'slug' => 'total-cholesterol', 'unit' => 'mg/dL', 'decimals' => 0],
                ['name' => 'HDL Cholesterol', 'slug' => 'hdl', 'unit' => 'mg/dL', 'decimals' => 0],
                ['name' => 'LDL Cholesterol', 'slug' => 'ldl', 'unit' => 'mg/dL', 'decimals' => 0],
                ['name' => 'Triglycerides', 'slug' => 'triglycerides', 'unit' => 'mg/dL', 'decimals' => 0],
            ],
            'sort_order' => 2,
        ],
        [
            'name' => 'Liver Function Test (LFT)',
            'slug' => 'liver-function-test',
            'description' => 'Evaluates liver health by measuring enzymes, proteins, and bilirubin.',
            'tests' => [
                ['name' => 'Alanine Aminotransferase (ALT)', 'slug' => 'alt', 'unit' => 'IU/L', 'decimals' => 0],
                ['name' => 'Aspartate Aminotransferase (AST)', 'slug' => 'ast', 'unit' => 'IU/L', 'decimals' => 0],
                ['name' => 'Alkaline Phosphatase (ALP)', 'slug' => 'alp', 'unit' => 'IU/L', 'decimals' => 0],
                ['name' => 'Total Bilirubin', 'slug' => 'total-bilirubin', 'unit' => 'mg/dL', 'decimals' => 1],
                ['name' => 'Total Protein', 'slug' => 'total-protein', 'unit' => 'g/dL', 'decimals' => 1],
                ['name' => 'Albumin', 'slug' => 'albumin', 'unit' => 'g/dL', 'decimals' => 1],
            ],
            'sort_order' => 3,
        ],
        [
            'name' => 'Renal Function Test',
            'slug' => 'renal-function-test',
            'description' => 'Assesses kidney function through creatinine, urea, and electrolyte levels.',
            'tests' => [
                ['name' => 'Creatinine', 'slug' => 'creatinine', 'unit' => 'mg/dL', 'decimals' => 1],
                ['name' => 'Blood Urea Nitrogen (BUN)', 'slug' => 'bun', 'unit' => 'mg/dL', 'decimals' => 0],
                ['name' => 'Sodium (Na+)', 'slug' => 'sodium', 'unit' => 'mmol/L', 'decimals' => 0],
                ['name' => 'Potassium (K+)', 'slug' => 'potassium', 'unit' => 'mmol/L', 'decimals' => 1],
                ['name' => 'Chloride (Cl-)', 'slug' => 'chloride', 'unit' => 'mmol/L', 'decimals' => 0],
                ['name' => 'eGFR', 'slug' => 'egfr', 'unit' => 'mL/min/1.73m²', 'decimals' => 0],
            ],
            'sort_order' => 4,
        ],
        [
            'name' => 'Diabetic Panel',
            'slug' => 'diabetic-panel',
            'description' => 'Measures blood sugar control over time to screen for or monitor diabetes.',
            'tests' => [
                ['name' => 'Fasting Blood Glucose', 'slug' => 'fasting-glucose', 'unit' => 'mg/dL', 'decimals' => 0],
                ['name' => 'HbA1c (Glycated Haemoglobin)', 'slug' => 'hba1c', 'unit' => '%', 'decimals' => 1],
                ['name' => 'Random Blood Glucose', 'slug' => 'random-glucose', 'unit' => 'mg/dL', 'decimals' => 0],
            ],
            'sort_order' => 5,
        ],
        [
            'name' => 'Thyroid Function Test',
            'slug' => 'thyroid-function-test',
            'description' => 'Evaluates thyroid gland activity by measuring TSH, T3, and T4 levels.',
            'tests' => [
                ['name' => 'TSH', 'slug' => 'tsh', 'unit' => 'mIU/L', 'decimals' => 2],
                ['name' => 'Free T3', 'slug' => 'free-t3', 'unit' => 'pg/mL', 'decimals' => 1],
                ['name' => 'Free T4', 'slug' => 'free-t4', 'unit' => 'ng/dL', 'decimals' => 1],
            ],
            'sort_order' => 6,
        ],
        [
            'name' => 'Iron Studies',
            'slug' => 'iron-studies',
            'description' => 'Assesses iron metabolism to diagnose anaemia and iron overload conditions.',
            'tests' => [
                ['name' => 'Serum Iron', 'slug' => 'serum-iron', 'unit' => 'µg/dL', 'decimals' => 0],
                ['name' => 'Ferritin', 'slug' => 'ferritin', 'unit' => 'ng/mL', 'decimals' => 0],
                ['name' => 'Total Iron Binding Capacity (TIBC)', 'slug' => 'tibc', 'unit' => 'µg/dL', 'decimals' => 0],
                ['name' => 'Transferrin Saturation', 'slug' => 'transferrin-saturation', 'unit' => '%', 'decimals' => 1],
            ],
            'sort_order' => 7,
        ],
    ];

    /**
     * Reference ranges keyed by test_slug.
     * Sources: Standard clinical laboratory reference ranges
     * (e.g., Mayo Clinic Laboratories, NHANES, UK-RCPA).
     * All ranges are in the units specified above.
     */
    private array $ranges = [
        'haemoglobin' => [
            'test_name' => 'Haemoglobin (Hb)',
            'unit' => 'g/dL',
            'decimals' => 1,
            'range_low_male' => 13.5, 'range_high_male' => 17.5,
            'range_low_female' => 12.0, 'range_high_female' => 15.5,
            'range_low_pregnant' => 11.0, 'range_high_pregnant' => 14.0,
            'range_low_pediatric' => 11.5, 'range_high_pediatric' => 15.5,
            'critical_low' => 7.0, 'critical_high' => 20.0,
            'source' => 'WHO Haemoglobin Concentrations 2011',
        ],
        'wbc' => [
            'test_name' => 'White Blood Cell Count (WBC)',
            'unit' => '×10⁹/L',
            'decimals' => 1,
            'range_low_male' => 4.0, 'range_high_male' => 11.0,
            'range_low_female' => 4.0, 'range_high_female' => 11.0,
            'range_low_pregnant' => 5.6, 'range_high_pregnant' => 16.9,
            'range_low_pediatric' => 5.0, 'range_high_pediatric' => 15.0,
            'critical_low' => 2.0, 'critical_high' => 30.0,
            'source' => 'Standard haematology ranges',
        ],
        'rbc' => [
            'test_name' => 'Red Blood Cell Count (RBC)',
            'unit' => '×10¹²/L',
            'decimals' => 2,
            'range_low_male' => 4.50, 'range_high_male' => 6.00,
            'range_low_female' => 4.00, 'range_high_female' => 5.20,
            'range_low_pregnant' => 3.80, 'range_high_pregnant' => 5.00,
            'range_low_pediatric' => 4.10, 'range_high_pediatric' => 5.50,
            'critical_low' => 2.50, 'critical_high' => 7.00,
            'source' => 'WHO RBC ranges',
        ],
        'hct' => [
            'test_name' => 'Haematocrit (HCT)',
            'unit' => '%',
            'decimals' => 1,
            'range_low_male' => 40.0, 'range_high_male' => 52.0,
            'range_low_female' => 36.0, 'range_high_female' => 48.0,
            'range_low_pregnant' => 33.0, 'range_high_pregnant' => 44.0,
            'range_low_pediatric' => 34.0, 'range_high_pediatric' => 44.0,
            'critical_low' => 21.0, 'critical_high' => 60.0,
            'source' => 'NHLBI haematocrit ranges',
        ],
        'mcv' => [
            'test_name' => 'Mean Corpuscular Volume (MCV)',
            'unit' => 'fL',
            'decimals' => 1,
            'range_low_male' => 80.0, 'range_high_male' => 100.0,
            'range_low_female' => 80.0, 'range_high_female' => 100.0,
            'critical_low' => 60.0, 'critical_high' => 120.0,
            'source' => 'Standard MCV reference',
        ],
        'platelets' => [
            'test_name' => 'Platelet Count',
            'unit' => '×10⁹/L',
            'decimals' => 0,
            'range_low_male' => 150, 'range_high_male' => 450,
            'range_low_female' => 150, 'range_high_female' => 450,
            'critical_low' => 50, 'critical_high' => 1000,
            'source' => 'BSCH platelet guidelines',
        ],
        'total-cholesterol' => [
            'test_name' => 'Total Cholesterol',
            'unit' => 'mg/dL',
            'decimals' => 0,
            'range_low_male' => 125, 'range_high_male' => 200,
            'range_low_female' => 125, 'range_high_female' => 200,
            'critical_high' => 300,
            'source' => 'AHA/NHLBI lipid guidelines',
        ],
        'hdl' => [
            'test_name' => 'HDL Cholesterol',
            'unit' => 'mg/dL',
            'decimals' => 0,
            'range_low_male' => 40, 'range_high_male' => 60,
            'range_low_female' => 50, 'range_high_female' => 70,
            'critical_low' => 20,
            'source' => 'AHA/NHLBI lipid guidelines',
        ],
        'ldl' => [
            'test_name' => 'LDL Cholesterol',
            'unit' => 'mg/dL',
            'decimals' => 0,
            'range_high_male' => 100,
            'range_high_female' => 100,
            'critical_high' => 190,
            'source' => 'AHA/NHLBI lipid guidelines',
        ],
        'triglycerides' => [
            'test_name' => 'Triglycerides',
            'unit' => 'mg/dL',
            'decimals' => 0,
            'range_high_male' => 150,
            'range_high_female' => 150,
            'critical_high' => 500,
            'source' => 'AHA/NHLBI lipid guidelines',
        ],
        'alt' => [
            'test_name' => 'Alanine Aminotransferase (ALT)',
            'unit' => 'IU/L',
            'decimals' => 0,
            'range_low_male' => 10, 'range_high_male' => 40,
            'range_low_female' => 7, 'range_high_female' => 35,
            'critical_high' => 200,
            'source' => 'ACG liver chemistry guidelines',
        ],
        'ast' => [
            'test_name' => 'Aspartate Aminotransferase (AST)',
            'unit' => 'IU/L',
            'decimals' => 0,
            'range_low_male' => 10, 'range_high_male' => 40,
            'range_low_female' => 9, 'range_high_female' => 32,
            'critical_high' => 200,
            'source' => 'ACG liver chemistry guidelines',
        ],
        'alp' => [
            'test_name' => 'Alkaline Phosphatase (ALP)',
            'unit' => 'IU/L',
            'decimals' => 0,
            'range_low_male' => 40, 'range_high_male' => 130,
            'range_low_female' => 35, 'range_high_female' => 105,
            'range_low_pregnant' => 60, 'range_high_pregnant' => 250, // ALP rises in pregnancy
            'range_low_pediatric' => 100, 'range_high_pediatric' => 350, // Higher in growing children
            'critical_high' => 500,
            'source' => 'Tietz Textbook of Clinical Chemistry',
        ],
        'total-bilirubin' => [
            'test_name' => 'Total Bilirubin',
            'unit' => 'mg/dL',
            'decimals' => 1,
            'range_low_male' => 0.1, 'range_high_male' => 1.2,
            'range_low_female' => 0.1, 'range_high_female' => 1.2,
            'critical_high' => 3.0,
            'source' => 'Bilirubin reference ranges',
        ],
        'total-protein' => [
            'test_name' => 'Total Protein',
            'unit' => 'g/dL',
            'decimals' => 1,
            'range_low_male' => 6.0, 'range_high_male' => 8.3,
            'range_low_female' => 6.0, 'range_high_female' => 8.3,
            'critical_low' => 4.0, 'critical_high' => 10.0,
            'source' => 'Clinical Chemistry Reference',
        ],
        'albumin' => [
            'test_name' => 'Albumin',
            'unit' => 'g/dL',
            'decimals' => 1,
            'range_low_male' => 3.5, 'range_high_male' => 5.0,
            'range_low_female' => 3.5, 'range_high_female' => 5.0,
            'critical_low' => 2.0,
            'source' => 'Clinical Chemistry Reference',
        ],
        'creatinine' => [
            'test_name' => 'Creatinine',
            'unit' => 'mg/dL',
            'decimals' => 1,
            'range_low_male' => 0.7, 'range_high_male' => 1.3,
            'range_low_female' => 0.6, 'range_high_female' => 1.1,
            'range_low_pregnant' => 0.4, 'range_high_pregnant' => 0.8,
            'critical_high' => 3.0,
            'source' => 'KDIGO CKD guidelines',
        ],
        'bun' => [
            'test_name' => 'Blood Urea Nitrogen (BUN)',
            'unit' => 'mg/dL',
            'decimals' => 0,
            'range_low_male' => 7, 'range_high_male' => 20,
            'range_low_female' => 7, 'range_high_female' => 20,
            'critical_high' => 50,
            'source' => 'KDIGO CKD guidelines',
        ],
        'sodium' => [
            'test_name' => 'Sodium (Na+)',
            'unit' => 'mmol/L',
            'decimals' => 0,
            'range_low_male' => 135, 'range_high_male' => 145,
            'range_low_female' => 135, 'range_high_female' => 145,
            'critical_low' => 125, 'critical_high' => 155,
            'source' => 'Clinical electrolyte reference',
        ],
        'potassium' => [
            'test_name' => 'Potassium (K+)',
            'unit' => 'mmol/L',
            'decimals' => 1,
            'range_low_male' => 3.5, 'range_high_male' => 5.1,
            'range_low_female' => 3.5, 'range_high_female' => 5.1,
            'critical_low' => 2.8, 'critical_high' => 6.2,
            'source' => 'Clinical electrolyte reference',
        ],
        'chloride' => [
            'test_name' => 'Chloride (Cl-)',
            'unit' => 'mmol/L',
            'decimals' => 0,
            'range_low_male' => 96, 'range_high_male' => 106,
            'range_low_female' => 96, 'range_high_female' => 106,
            'critical_low' => 85, 'critical_high' => 115,
            'source' => 'Clinical electrolyte reference',
        ],
        'egfr' => [
            'test_name' => 'eGFR',
            'unit' => 'mL/min/1.73m²',
            'decimals' => 0,
            'range_low_male' => 90, 'range_low_female' => 90,
            'critical_low' => 15,
            'source' => 'KDIGO CKD staging',
        ],
        'fasting-glucose' => [
            'test_name' => 'Fasting Blood Glucose',
            'unit' => 'mg/dL',
            'decimals' => 0,
            'range_low_male' => 70, 'range_high_male' => 99,
            'range_low_female' => 70, 'range_high_female' => 99,
            'range_low_pregnant' => 60, 'range_high_pregnant' => 95, // Gestational diabetes screening lower threshold
            'critical_low' => 50, 'critical_high' => 300,
            'source' => 'ADA diabetes guidelines 2024',
        ],
        'hba1c' => [
            'test_name' => 'HbA1c (Glycated Haemoglobin)',
            'unit' => '%',
            'decimals' => 1,
            'range_low_male' => 4.0, 'range_high_male' => 5.6,
            'range_low_female' => 4.0, 'range_high_female' => 5.6,
            'critical_high' => 10.0,
            'source' => 'ADA diabetes guidelines 2024',
        ],
        'random-glucose' => [
            'test_name' => 'Random Blood Glucose',
            'unit' => 'mg/dL',
            'decimals' => 0,
            'range_high_male' => 140,
            'range_high_female' => 140,
            'critical_low' => 50, 'critical_high' => 400,
            'source' => 'ADA guidelines',
        ],
        'tsh' => [
            'test_name' => 'TSH',
            'unit' => 'mIU/L',
            'decimals' => 2,
            'range_low_male' => 0.40, 'range_high_male' => 4.50,
            'range_low_female' => 0.40, 'range_high_female' => 4.50,
            'range_low_pregnant' => 0.10, 'range_high_pregnant' => 3.50, // TSH decreases in early pregnancy
            'critical_low' => 0.01, 'critical_high' => 10.00,
            'source' => 'ATA thyroid guidelines',
        ],
        'free-t3' => [
            'test_name' => 'Free T3',
            'unit' => 'pg/mL',
            'decimals' => 1,
            'range_low_male' => 2.3, 'range_high_male' => 4.2,
            'range_low_female' => 2.3, 'range_high_female' => 4.2,
            'critical_low' => 1.0, 'critical_high' => 8.0,
            'source' => 'ATA thyroid guidelines',
        ],
        'free-t4' => [
            'test_name' => 'Free T4',
            'unit' => 'ng/dL',
            'decimals' => 1,
            'range_low_male' => 0.8, 'range_high_male' => 1.8,
            'range_low_female' => 0.8, 'range_high_female' => 1.8,
            'critical_low' => 0.3, 'critical_high' => 3.5,
            'source' => 'ATA thyroid guidelines',
        ],
        'serum-iron' => [
            'test_name' => 'Serum Iron',
            'unit' => 'µg/dL',
            'decimals' => 0,
            'range_low_male' => 65, 'range_high_male' => 176,
            'range_low_female' => 50, 'range_high_female' => 170,
            'critical_high' => 300,
            'source' => 'American Society of Hematology',
        ],
        'ferritin' => [
            'test_name' => 'Ferritin',
            'unit' => 'ng/mL',
            'decimals' => 0,
            'range_low_male' => 30, 'range_high_male' => 400,
            'range_low_female' => 15, 'range_high_female' => 150,
            'critical_low' => 10, 'critical_high' => 1000,
            'source' => 'WHO iron deficiency guidelines',
        ],
        'tibc' => [
            'test_name' => 'Total Iron Binding Capacity (TIBC)',
            'unit' => 'µg/dL',
            'decimals' => 0,
            'range_low_male' => 250, 'range_high_male' => 450,
            'range_low_female' => 250, 'range_high_female' => 450,
            'source' => 'Clinical iron studies reference',
        ],
        'transferrin-saturation' => [
            'test_name' => 'Transferrin Saturation',
            'unit' => '%',
            'decimals' => 1,
            'range_low_male' => 20, 'range_high_male' => 50,
            'range_low_female' => 15, 'range_high_female' => 50,
            'critical_low' => 10, 'critical_high' => 60,
            'source' => 'Clinical iron studies reference',
        ],
    ];

    public function run(): void
    {
        foreach ($this->panels as $panel) {
            $created = TestPanel::firstOrCreate(
                ['slug' => $panel['slug']],
                [
                    'name' => $panel['name'],
                    'description' => $panel['description'],
                    'tests' => $panel['tests'],
                    'sort_order' => $panel['sort_order'],
                ],
            );

            // Seed reference ranges for each test in the panel
            foreach ($panel['tests'] as $test) {
                $slug = $test['slug'];
                if (isset($this->ranges[$slug])) {
                    $r = $this->ranges[$slug];
                    TestReferenceRange::firstOrCreate(
                        ['test_panel_id' => $created->id, 'test_slug' => $slug],
                        array_merge(['test_panel_id' => $created->id], $r),
                    );
                }
            }
        }
    }
}
