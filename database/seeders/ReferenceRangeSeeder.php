<?php

namespace Database\Seeders;

use App\Models\ReferenceRange;
use Illuminate\Database\Seeder;

class ReferenceRangeSeeder extends Seeder
{
    public function run(): void
    {
        $ranges = [

            // ── HAEMATOLOGY ──────────────────────────────────────────

            // Haemoglobin
            ['test_code' => 'haemoglobin', 'test_name' => 'Haemoglobin', 'category' => 'haematology', 'sex' => 'male', 'age_min_years' => 18, 'age_max_years' => null, 'range_low' => 13.0, 'range_high' => 17.0, 'critical_low' => 7.0, 'critical_high' => 20.0, 'unit' => 'g/dL', 'source' => 'WHO 2024'],
            ['test_code' => 'haemoglobin', 'test_name' => 'Haemoglobin', 'category' => 'haematology', 'sex' => 'female', 'age_min_years' => 18, 'age_max_years' => null, 'range_low' => 12.0, 'range_high' => 15.5, 'critical_low' => 7.0, 'critical_high' => 20.0, 'unit' => 'g/dL', 'source' => 'WHO 2024'],
            ['test_code' => 'haemoglobin', 'test_name' => 'Haemoglobin', 'category' => 'haematology', 'sex' => 'female', 'age_min_years' => 18, 'age_max_years' => null, 'pregnancy_applicable' => true, 'pregnancy_trimester' => null, 'range_low' => 11.0, 'range_high' => 14.0, 'critical_low' => 7.0, 'critical_high' => 20.0, 'unit' => 'g/dL', 'source' => 'WHO 2024'],
            ['test_code' => 'haemoglobin', 'test_name' => 'Haemoglobin', 'category' => 'haematology', 'sex' => 'all', 'age_min_years' => 1, 'age_max_years' => 5, 'range_low' => 11.0, 'range_high' => 14.0, 'critical_low' => 7.0, 'critical_high' => 18.0, 'unit' => 'g/dL', 'source' => 'WHO 2024'],
            ['test_code' => 'haemoglobin', 'test_name' => 'Haemoglobin', 'category' => 'haematology', 'sex' => 'all', 'age_min_years' => 6, 'age_max_years' => 12, 'range_low' => 11.5, 'range_high' => 15.5, 'critical_low' => 7.0, 'critical_high' => 18.0, 'unit' => 'g/dL', 'source' => 'WHO 2024'],
            ['test_code' => 'haemoglobin', 'test_name' => 'Haemoglobin', 'category' => 'haematology', 'sex' => 'all', 'age_min_years' => 13, 'age_max_years' => 17, 'pregnancy_applicable' => false, 'range_low' => 12.0, 'range_high' => 16.0, 'critical_low' => 7.0, 'critical_high' => 18.0, 'unit' => 'g/dL', 'source' => 'WHO 2024'],

            // PCV (Haematocrit)
            ['test_code' => 'pcv', 'test_name' => 'PCV (Haematocrit)', 'category' => 'haematology', 'sex' => 'male', 'range_low' => 40, 'range_high' => 52, 'critical_low' => 20, 'critical_high' => 60, 'unit' => '%', 'source' => 'WHO 2024'],
            ['test_code' => 'pcv', 'test_name' => 'PCV (Haematocrit)', 'category' => 'haematology', 'sex' => 'female', 'range_low' => 36, 'range_high' => 47, 'critical_low' => 20, 'critical_high' => 60, 'unit' => '%', 'source' => 'WHO 2024'],

            // WBC
            ['test_code' => 'wbc', 'test_name' => 'White Blood Cell Count', 'category' => 'haematology', 'sex' => 'all', 'age_min_years' => 18, 'range_low' => 4.0, 'range_high' => 11.0, 'critical_low' => 1.5, 'critical_high' => 30.0, 'unit' => '10^9/L', 'source' => 'NCCLS'],
            ['test_code' => 'wbc', 'test_name' => 'White Blood Cell Count', 'category' => 'haematology', 'sex' => 'all', 'age_min_years' => 0, 'age_max_years' => 1, 'range_low' => 6.0, 'range_high' => 18.0, 'critical_low' => 2.0, 'critical_high' => 30.0, 'unit' => '10^9/L', 'source' => 'NCCLS'],

            // RBC
            ['test_code' => 'rbc', 'test_name' => 'Red Blood Cell Count', 'category' => 'haematology', 'sex' => 'male', 'range_low' => 4.5, 'range_high' => 6.0, 'critical_low' => 2.5, 'critical_high' => 7.5, 'unit' => '10^12/L', 'source' => 'WHO 2024'],
            ['test_code' => 'rbc', 'test_name' => 'Red Blood Cell Count', 'category' => 'haematology', 'sex' => 'female', 'range_low' => 4.0, 'range_high' => 5.2, 'critical_low' => 2.5, 'critical_high' => 7.5, 'unit' => '10^12/L', 'source' => 'WHO 2024'],

            // Platelets
            ['test_code' => 'platelets', 'test_name' => 'Platelet Count', 'category' => 'haematology', 'sex' => 'all', 'range_low' => 150, 'range_high' => 450, 'critical_low' => 50, 'critical_high' => 1000, 'unit' => '10^9/L', 'source' => 'WHO 2024'],

            // MCV
            ['test_code' => 'mcv', 'test_name' => 'Mean Corpuscular Volume (MCV)', 'category' => 'haematology', 'sex' => 'all', 'range_low' => 80, 'range_high' => 100, 'critical_low' => 65, 'critical_high' => 120, 'unit' => 'fL', 'source' => 'NCCLS'],

            // MCH
            ['test_code' => 'mch', 'test_name' => 'Mean Corpuscular Haemoglobin (MCH)', 'category' => 'haematology', 'sex' => 'all', 'range_low' => 27, 'range_high' => 32, 'unit' => 'pg', 'source' => 'NCCLS'],

            // MCHC
            ['test_code' => 'mchc', 'test_name' => 'Mean Corpuscular Hb Concentration (MCHC)', 'category' => 'haematology', 'sex' => 'all', 'range_low' => 32, 'range_high' => 36, 'unit' => 'g/dL', 'source' => 'NCCLS'],

            // RDW
            ['test_code' => 'rdw', 'test_name' => 'Red Cell Distribution Width (RDW)', 'category' => 'haematology', 'sex' => 'all', 'range_low' => 11.5, 'range_high' => 14.5, 'unit' => '%', 'source' => 'NCCLS'],

            // ESR
            ['test_code' => 'esr', 'test_name' => 'Erythrocyte Sedimentation Rate (ESR)', 'category' => 'haematology', 'sex' => 'male', 'range_low' => 0, 'range_high' => 15, 'unit' => 'mm/hr', 'source' => 'NCCLS'],
            ['test_code' => 'esr', 'test_name' => 'Erythrocyte Sedimentation Rate (ESR)', 'category' => 'haematology', 'sex' => 'female', 'range_low' => 0, 'range_high' => 20, 'unit' => 'mm/hr', 'source' => 'NCCLS'],

            // Neutrophils
            ['test_code' => 'neutrophils', 'test_name' => 'Neutrophils', 'category' => 'haematology', 'sex' => 'all', 'range_low' => 40, 'range_high' => 75, 'critical_low' => 15, 'unit' => '%', 'source' => 'NCCLS'],

            // Lymphocytes
            ['test_code' => 'lymphocytes', 'test_name' => 'Lymphocytes', 'category' => 'haematology', 'sex' => 'all', 'range_low' => 20, 'range_high' => 45, 'unit' => '%', 'source' => 'NCCLS'],

            // ── CHEMISTRY / LIVER ────────────────────────────────────

            // ALT
            ['test_code' => 'alt', 'test_name' => 'ALT (SGPT)', 'category' => 'chemistry', 'sex' => 'male', 'range_low' => 10, 'range_high' => 40, 'critical_high' => 200, 'unit' => 'IU/L', 'source' => 'NCCLS'],
            ['test_code' => 'alt', 'test_name' => 'ALT (SGPT)', 'category' => 'chemistry', 'sex' => 'female', 'range_low' => 7, 'range_high' => 35, 'critical_high' => 200, 'unit' => 'IU/L', 'source' => 'NCCLS'],

            // AST
            ['test_code' => 'ast', 'test_name' => 'AST (SGOT)', 'category' => 'chemistry', 'sex' => 'male', 'range_low' => 10, 'range_high' => 40, 'critical_high' => 200, 'unit' => 'IU/L', 'source' => 'NCCLS'],
            ['test_code' => 'ast', 'test_name' => 'AST (SGOT)', 'category' => 'chemistry', 'sex' => 'female', 'range_low' => 9, 'range_high' => 32, 'critical_high' => 200, 'unit' => 'IU/L', 'source' => 'NCCLS'],

            // ALP
            ['test_code' => 'alp', 'test_name' => 'Alkaline Phosphatase (ALP)', 'category' => 'chemistry', 'sex' => 'all', 'age_min_years' => 18, 'range_low' => 40, 'range_high' => 130, 'critical_high' => 400, 'unit' => 'IU/L', 'source' => 'NCCLS'],
            ['test_code' => 'alp', 'test_name' => 'Alkaline Phosphatase (ALP)', 'category' => 'chemistry', 'sex' => 'all', 'age_min_years' => 0, 'age_max_years' => 17, 'range_low' => 100, 'range_high' => 400, 'unit' => 'IU/L', 'source' => 'NCCLS'],

            // GGT
            ['test_code' => 'ggt', 'test_name' => 'Gamma-Glutamyl Transferase (GGT)', 'category' => 'chemistry', 'sex' => 'male', 'range_low' => 10, 'range_high' => 50, 'unit' => 'IU/L', 'source' => 'NCCLS'],
            ['test_code' => 'ggt', 'test_name' => 'Gamma-Glutamyl Transferase (GGT)', 'category' => 'chemistry', 'sex' => 'female', 'range_low' => 7, 'range_high' => 32, 'unit' => 'IU/L', 'source' => 'NCCLS'],

            // Total Bilirubin
            ['test_code' => 'total_bilirubin', 'test_name' => 'Total Bilirubin', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 0.3, 'range_high' => 1.2, 'critical_high' => 5.0, 'unit' => 'mg/dL', 'source' => 'NCCLS'],

            // Direct Bilirubin
            ['test_code' => 'direct_bilirubin', 'test_name' => 'Direct Bilirubin', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 0.0, 'range_high' => 0.3, 'unit' => 'mg/dL', 'source' => 'NCCLS'],

            // Total Protein
            ['test_code' => 'total_protein', 'test_name' => 'Total Protein', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 6.0, 'range_high' => 8.3, 'critical_low' => 4.0, 'unit' => 'g/dL', 'source' => 'NCCLS'],

            // Albumin
            ['test_code' => 'albumin', 'test_name' => 'Albumin', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 3.5, 'range_high' => 5.0, 'critical_low' => 2.5, 'unit' => 'g/dL', 'source' => 'NCCLS'],

            // Globulin
            ['test_code' => 'globulin', 'test_name' => 'Globulin', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 2.0, 'range_high' => 3.5, 'unit' => 'g/dL', 'source' => 'NCCLS'],

            // ── RENAL ─────────────────────────────────────────────────

            // Creatinine
            ['test_code' => 'creatinine', 'test_name' => 'Creatinine', 'category' => 'chemistry', 'sex' => 'male', 'age_min_years' => 18, 'range_low' => 0.7, 'range_high' => 1.3, 'critical_high' => 5.0, 'unit' => 'mg/dL', 'source' => 'NKF / KDOQI'],
            ['test_code' => 'creatinine', 'test_name' => 'Creatinine', 'category' => 'chemistry', 'sex' => 'female', 'age_min_years' => 18, 'range_low' => 0.6, 'range_high' => 1.1, 'critical_high' => 5.0, 'unit' => 'mg/dL', 'source' => 'NKF / KDOQI'],

            // Urea
            ['test_code' => 'urea', 'test_name' => 'Urea', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 15, 'range_high' => 45, 'critical_high' => 100, 'unit' => 'mg/dL', 'source' => 'NCCLS'],

            // BUN
            ['test_code' => 'bun', 'test_name' => 'Blood Urea Nitrogen (BUN)', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 7, 'range_high' => 20, 'unit' => 'mg/dL', 'source' => 'NCCLS'],

            // Uric Acid
            ['test_code' => 'uric_acid', 'test_name' => 'Uric Acid', 'category' => 'chemistry', 'sex' => 'male', 'range_low' => 3.4, 'range_high' => 7.0, 'unit' => 'mg/dL', 'source' => 'NCCLS'],
            ['test_code' => 'uric_acid', 'test_name' => 'Uric Acid', 'category' => 'chemistry', 'sex' => 'female', 'range_low' => 2.4, 'range_high' => 6.0, 'unit' => 'mg/dL', 'source' => 'NCCLS'],

            // ── ELECTROLYTES ──────────────────────────────────────────

            ['test_code' => 'sodium', 'test_name' => 'Sodium (Na+)', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 135, 'range_high' => 145, 'critical_low' => 120, 'critical_high' => 160, 'unit' => 'mmol/L', 'source' => 'NCCLS'],
            ['test_code' => 'potassium', 'test_name' => 'Potassium (K+)', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 3.5, 'range_high' => 5.1, 'critical_low' => 2.5, 'critical_high' => 6.5, 'unit' => 'mmol/L', 'source' => 'NCCLS'],
            ['test_code' => 'chloride', 'test_name' => 'Chloride (Cl-)', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 98, 'range_high' => 107, 'unit' => 'mmol/L', 'source' => 'NCCLS'],
            ['test_code' => 'bicarbonate', 'test_name' => 'Bicarbonate (HCO3-)', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 22, 'range_high' => 29, 'critical_low' => 15, 'unit' => 'mmol/L', 'source' => 'NCCLS'],

            // ── GLUCOSE ───────────────────────────────────────────────

            ['test_code' => 'glucose', 'test_name' => 'Fasting Blood Glucose', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 70, 'range_high' => 100, 'critical_low' => 50, 'critical_high' => 300, 'unit' => 'mg/dL', 'source' => 'ADA 2024'],
            ['test_code' => 'glucose_random', 'test_name' => 'Random Blood Glucose', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 70, 'range_high' => 140, 'critical_high' => 300, 'unit' => 'mg/dL', 'source' => 'ADA 2024'],
            ['test_code' => 'hba1c', 'test_name' => 'HbA1c', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 4.0, 'range_high' => 5.6, 'critical_high' => 9.0, 'unit' => '%', 'source' => 'ADA 2024'],

            // ── LIPIDS ────────────────────────────────────────────────

            ['test_code' => 'total_cholesterol', 'test_name' => 'Total Cholesterol', 'category' => 'lipids', 'sex' => 'all', 'range_low' => 125, 'range_high' => 200, 'critical_high' => 300, 'unit' => 'mg/dL', 'source' => 'NCEP ATP III'],
            ['test_code' => 'ldl', 'test_name' => 'LDL Cholesterol', 'category' => 'lipids', 'sex' => 'all', 'range_low' => 0, 'range_high' => 130, 'critical_high' => 190, 'unit' => 'mg/dL', 'source' => 'NCEP ATP III'],
            ['test_code' => 'hdl', 'test_name' => 'HDL Cholesterol', 'category' => 'lipids', 'sex' => 'male', 'range_low' => 35, 'range_high' => 65, 'critical_low' => 20, 'unit' => 'mg/dL', 'source' => 'NCEP ATP III'],
            ['test_code' => 'hdl', 'test_name' => 'HDL Cholesterol', 'category' => 'lipids', 'sex' => 'female', 'range_low' => 40, 'range_high' => 75, 'critical_low' => 20, 'unit' => 'mg/dL', 'source' => 'NCEP ATP III'],
            ['test_code' => 'triglycerides', 'test_name' => 'Triglycerides', 'category' => 'lipids', 'sex' => 'all', 'range_low' => 0, 'range_high' => 150, 'critical_high' => 500, 'unit' => 'mg/dL', 'source' => 'NCEP ATP III'],

            // ── THYROID ───────────────────────────────────────────────

            ['test_code' => 'tsh', 'test_name' => 'TSH', 'category' => 'thyroid', 'sex' => 'all', 'age_min_years' => 18, 'range_low' => 0.4, 'range_high' => 4.5, 'critical_low' => 0.01, 'critical_high' => 10.0, 'unit' => 'mIU/L', 'source' => 'ATA 2024'],
            ['test_code' => 'ft3', 'test_name' => 'Free T3', 'category' => 'thyroid', 'sex' => 'all', 'range_low' => 2.3, 'range_high' => 4.2, 'unit' => 'pg/mL', 'source' => 'ATA 2024'],
            ['test_code' => 'ft4', 'test_name' => 'Free T4', 'category' => 'thyroid', 'sex' => 'all', 'range_low' => 0.8, 'range_high' => 1.8, 'unit' => 'ng/dL', 'source' => 'ATA 2024'],

            // ── CARDIAC ───────────────────────────────────────────────

            ['test_code' => 'ck', 'test_name' => 'Creatine Kinase (CK)', 'category' => 'chemistry', 'sex' => 'male', 'range_low' => 38, 'range_high' => 174, 'unit' => 'IU/L', 'source' => 'NCCLS'],
            ['test_code' => 'ck', 'test_name' => 'Creatine Kinase (CK)', 'category' => 'chemistry', 'sex' => 'female', 'range_low' => 26, 'range_high' => 140, 'unit' => 'IU/L', 'source' => 'NCCLS'],
            ['test_code' => 'ldh', 'test_name' => 'Lactate Dehydrogenase (LDH)', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 140, 'range_high' => 280, 'unit' => 'IU/L', 'source' => 'NCCLS'],

            // ── INFLAMMATION ──────────────────────────────────────────

            ['test_code' => 'crp', 'test_name' => 'C-Reactive Protein (CRP)', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 0, 'range_high' => 5, 'unit' => 'mg/L', 'source' => 'NCCLS'],

            // ── IRON STUDIES ──────────────────────────────────────────

            ['test_code' => 'iron', 'test_name' => 'Serum Iron', 'category' => 'chemistry', 'sex' => 'male', 'range_low' => 65, 'range_high' => 176, 'unit' => 'µg/dL', 'source' => 'NCCLS'],
            ['test_code' => 'iron', 'test_name' => 'Serum Iron', 'category' => 'chemistry', 'sex' => 'female', 'range_low' => 50, 'range_high' => 170, 'unit' => 'µg/dL', 'source' => 'NCCLS'],
            ['test_code' => 'ferritin', 'test_name' => 'Ferritin', 'category' => 'chemistry', 'sex' => 'male', 'range_low' => 30, 'range_high' => 400, 'unit' => 'ng/mL', 'source' => 'NCCLS'],
            ['test_code' => 'ferritin', 'test_name' => 'Ferritin', 'category' => 'chemistry', 'sex' => 'female', 'range_low' => 15, 'range_high' => 200, 'unit' => 'ng/mL', 'source' => 'NCCLS'],

            // ── VITAMINS ──────────────────────────────────────────────

            ['test_code' => 'vitamin_d', 'test_name' => 'Vitamin D (25-OH)', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 30, 'range_high' => 100, 'critical_low' => 10, 'unit' => 'ng/mL', 'source' => 'Endocrine Society'],
            ['test_code' => 'b12', 'test_name' => 'Vitamin B12', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 200, 'range_high' => 900, 'critical_low' => 100, 'unit' => 'pg/mL', 'source' => 'NCCLS'],
            ['test_code' => 'folate', 'test_name' => 'Folate', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 3, 'range_high' => 20, 'critical_low' => 2, 'unit' => 'ng/mL', 'source' => 'NCCLS'],

            // ── MINERALS ──────────────────────────────────────────────

            ['test_code' => 'calcium', 'test_name' => 'Calcium (Total)', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 8.5, 'range_high' => 10.5, 'critical_low' => 6.5, 'critical_high' => 13.0, 'unit' => 'mg/dL', 'source' => 'NCCLS'],
            ['test_code' => 'magnesium', 'test_name' => 'Magnesium', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 1.7, 'range_high' => 2.2, 'critical_low' => 1.0, 'unit' => 'mg/dL', 'source' => 'NCCLS'],
            ['test_code' => 'phosphate', 'test_name' => 'Phosphate', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 2.5, 'range_high' => 4.5, 'unit' => 'mg/dL', 'source' => 'NCCLS'],

            // ── COAGULATION ───────────────────────────────────────────

            ['test_code' => 'pt', 'test_name' => 'Prothrombin Time (PT)', 'category' => 'haematology', 'sex' => 'all', 'range_low' => 11, 'range_high' => 14, 'critical_high' => 20, 'unit' => 'seconds', 'source' => 'NCCLS'],
            ['test_code' => 'ptt', 'test_name' => 'Partial Thromboplastin Time (PTT)', 'category' => 'haematology', 'sex' => 'all', 'range_low' => 25, 'range_high' => 35, 'unit' => 'seconds', 'source' => 'NCCLS'],
            ['test_code' => 'inr', 'test_name' => 'INR', 'category' => 'haematology', 'sex' => 'all', 'range_low' => 0.9, 'range_high' => 1.1, 'critical_high' => 3.5, 'unit' => '-', 'source' => 'WHO'],

            // ── TUMOR MARKERS / SPECIAL ───────────────────────────────

            ['test_code' => 'psa', 'test_name' => 'Prostate Specific Antigen (PSA)', 'category' => 'chemistry', 'sex' => 'male', 'age_min_years' => 40, 'range_low' => 0, 'range_high' => 4.0, 'unit' => 'ng/mL', 'source' => 'NCCLS'],

            // ── SEROLOGY / INFECTIOUS DISEASE ─────────────────────────

            ['test_code' => 'hiv', 'test_name' => 'HIV Antibody', 'category' => 'serology', 'sex' => 'all', 'range_low' => 0, 'range_high' => 0, 'unit' => '-', 'source' => 'NCDC Nigeria'],
            ['test_code' => 'hbsag', 'test_name' => 'Hepatitis B Surface Antigen (HBsAg)', 'category' => 'serology', 'sex' => 'all', 'range_low' => 0, 'range_high' => 0, 'unit' => '-', 'source' => 'NCDC Nigeria'],
            ['test_code' => 'hcv', 'test_name' => 'Hepatitis C Antibody', 'category' => 'serology', 'sex' => 'all', 'range_low' => 0, 'range_high' => 0, 'unit' => '-', 'source' => 'NCDC Nigeria'],
            ['test_code' => 'malaria_parasite', 'test_name' => 'Malaria Parasite', 'category' => 'serology', 'sex' => 'all', 'range_low' => 0, 'range_high' => 0, 'unit' => '-', 'source' => 'NCDC Nigeria'],
            ['test_code' => 'widal', 'test_name' => 'Widal Test (Typhoid)', 'category' => 'serology', 'sex' => 'all', 'range_low' => 0, 'range_high' => 0, 'unit' => '-', 'source' => 'NCDC Nigeria'],

            // ── URINALYSIS ────────────────────────────────────────────

            ['test_code' => 'urine_ph', 'test_name' => 'Urine pH', 'category' => 'urinalysis', 'sex' => 'all', 'range_low' => 5.0, 'range_high' => 8.0, 'unit' => '-', 'source' => 'NCCLS'],
            ['test_code' => 'urine_protein', 'test_name' => 'Urine Protein', 'category' => 'urinalysis', 'sex' => 'all', 'range_low' => 0, 'range_high' => 0, 'unit' => 'mg/dL', 'source' => 'NCCLS'],
            ['test_code' => 'urine_glucose', 'test_name' => 'Urine Glucose', 'category' => 'urinalysis', 'sex' => 'all', 'range_low' => 0, 'range_high' => 0, 'unit' => 'mg/dL', 'source' => 'NCCLS'],

            // ── HORMONES ──────────────────────────────────────────────

            ['test_code' => 'prolactin', 'test_name' => 'Prolactin', 'category' => 'hormones', 'sex' => 'male', 'range_low' => 4, 'range_high' => 15, 'unit' => 'ng/mL', 'source' => 'NCCLS'],
            ['test_code' => 'prolactin', 'test_name' => 'Prolactin', 'category' => 'hormones', 'sex' => 'female', 'range_low' => 4, 'range_high' => 25, 'unit' => 'ng/mL', 'source' => 'NCCLS'],
            ['test_code' => 'testosterone', 'test_name' => 'Testosterone (Total)', 'category' => 'hormones', 'sex' => 'male', 'range_low' => 300, 'range_high' => 1000, 'unit' => 'ng/dL', 'source' => 'NCCLS'],
            ['test_code' => 'testosterone', 'test_name' => 'Testosterone (Total)', 'category' => 'hormones', 'sex' => 'female', 'range_low' => 15, 'range_high' => 70, 'unit' => 'ng/dL', 'source' => 'NCCLS'],

            // ── PREGNANCY ─────────────────────────────────────────────

            ['test_code' => 'pregnancy_test', 'test_name' => 'Pregnancy Test (β-hCG)', 'category' => 'hormones', 'sex' => 'female', 'range_low' => 0, 'range_high' => 5, 'unit' => 'mIU/mL', 'source' => 'NCCLS'],

            // ── MISCELLANEOUS ─────────────────────────────────────────

            ['test_code' => 'amylase', 'test_name' => 'Amylase', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 28, 'range_high' => 100, 'unit' => 'IU/L', 'source' => 'NCCLS'],
            ['test_code' => 'lipase', 'test_name' => 'Lipase', 'category' => 'chemistry', 'sex' => 'all', 'range_low' => 13, 'range_high' => 60, 'unit' => 'IU/L', 'source' => 'NCCLS'],
            ['test_code' => 'cortisol', 'test_name' => 'Cortisol (AM)', 'category' => 'hormones', 'sex' => 'all', 'range_low' => 6, 'range_high' => 23, 'unit' => 'µg/dL', 'source' => 'NCCLS'],
        ];

        foreach ($ranges as $data) {
            ReferenceRange::firstOrCreate(
                [
                    'test_code' => $data['test_code'],
                    'sex' => $data['sex'],
                    'age_min_years' => $data['age_min_years'] ?? null,
                    'age_max_years' => $data['age_max_years'] ?? null,
                    'pregnancy_applicable' => $data['pregnancy_applicable'] ?? false,
                    'pregnancy_trimester' => $data['pregnancy_trimester'] ?? null,
                ],
                $data,
            );
        }

        $this->command?->info('Seeded ' . count($ranges) . ' reference ranges.');
    }
}