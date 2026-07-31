<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicationEffectSeeder extends Seeder
{
    public function run(): void
    {
        $effects = [
            // ── Diabetes Medications ──
            ['medication_slug' => 'metformin', 'medication_name' => 'Metformin', 'test_code' => 'glucose', 'expected_effect' => 'lowers', 'severity' => 'moderate', 'clinician_note' => 'Metformin reduces hepatic glucose production. Lower glucose values are expected.'],
            ['medication_slug' => 'metformin', 'medication_name' => 'Metformin', 'test_code' => 'hba1c', 'expected_effect' => 'lowers', 'severity' => 'moderate', 'clinician_note' => 'Metformin improves glycaemic control. Lower HbA1c is expected.'],
            ['medication_slug' => 'glibenclamide', 'medication_name' => 'Glibenclamide', 'test_code' => 'glucose', 'expected_effect' => 'lowers', 'severity' => 'significant', 'clinician_note' => 'Sulphonylurea — stimulates insulin secretion. May cause hypoglycaemia.'],
            ['medication_slug' => 'insulin', 'medication_name' => 'Insulin', 'test_code' => 'glucose', 'expected_effect' => 'lowers', 'severity' => 'significant', 'clinician_note' => 'Exogenous insulin lowers blood glucose. Value should be interpreted in context of dosing.'],
            ['medication_slug' => 'insulin', 'medication_name' => 'Insulin', 'test_code' => 'potassium', 'expected_effect' => 'lowers', 'severity' => 'moderate', 'clinician_note' => 'Insulin drives potassium into cells. May cause hypokalaemia.'],

            // ── Hypertension / Cardiac ──
            ['medication_slug' => 'lisinopril', 'medication_name' => 'Lisinopril', 'test_code' => 'potassium', 'expected_effect' => 'elevates', 'severity' => 'moderate', 'clinician_note' => 'ACE inhibitor — reduces aldosterone, increasing potassium. Monitor for hyperkalaemia.'],
            ['medication_slug' => 'lisinopril', 'medication_name' => 'Lisinopril', 'test_code' => 'creatinine', 'expected_effect' => 'elevates', 'severity' => 'mild', 'clinician_note' => 'ACE inhibitors can cause a mild, reversible rise in creatinine.'],
            ['medication_slug' => 'losartan', 'medication_name' => 'Losartan', 'test_code' => 'potassium', 'expected_effect' => 'elevates', 'severity' => 'moderate', 'clinician_note' => 'ARB — similar to ACE inhibitors. Monitor potassium.'],
            ['medication_slug' => 'hydrochlorothiazide', 'medication_name' => 'Hydrochlorothiazide', 'test_code' => 'potassium', 'expected_effect' => 'lowers', 'severity' => 'moderate', 'clinician_note' => 'Thiazide diuretic — promotes potassium loss. May cause hypokalaemia.'],
            ['medication_slug' => 'hydrochlorothiazide', 'medication_name' => 'Hydrochlorothiazide', 'test_code' => 'sodium', 'expected_effect' => 'lowers', 'severity' => 'mild', 'clinician_note' => 'Thiazide diuretic — can cause mild hyponatraemia.'],
            ['medication_slug' => 'furosemide', 'medication_name' => 'Furosemide', 'test_code' => 'potassium', 'expected_effect' => 'lowers', 'severity' => 'significant', 'clinician_note' => 'Loop diuretic — significant potassium loss. Monitor closely.'],
            ['medication_slug' => 'furosemide', 'medication_name' => 'Furosemide', 'test_code' => 'creatinine', 'expected_effect' => 'elevates', 'severity' => 'mild', 'clinician_note' => 'May cause prerenal azotaemia from volume depletion.'],
            ['medication_slug' => 'spironolactone', 'medication_name' => 'Spironolactone', 'test_code' => 'potassium', 'expected_effect' => 'elevates', 'severity' => 'significant', 'clinician_note' => 'Potassium-sparing diuretic — significant hyperkalaemia risk.'],

            // ── Lipid-Lowering ──
            ['medication_slug' => 'atorvastatin', 'medication_name' => 'Atorvastatin', 'test_code' => 'alt', 'expected_effect' => 'elevates', 'severity' => 'mild', 'clinician_note' => 'Statins can cause mild, usually asymptomatic ALT elevation.'],
            ['medication_slug' => 'atorvastatin', 'medication_name' => 'Atorvastatin', 'test_code' => 'ast', 'expected_effect' => 'elevates', 'severity' => 'mild', 'clinician_note' => 'Statins can cause mild AST elevation.'],
            ['medication_slug' => 'atorvastatin', 'medication_name' => 'Atorvastatin', 'test_code' => 'ldl', 'expected_effect' => 'lowers', 'severity' => 'significant', 'clinician_note' => 'Statin — expected to significantly lower LDL cholesterol.'],
            ['medication_slug' => 'atorvastatin', 'medication_name' => 'Atorvastatin', 'test_code' => 'total_cholesterol', 'expected_effect' => 'lowers', 'severity' => 'significant', 'clinician_note' => 'Expected reduction in total cholesterol.'],
            ['medication_slug' => 'simvastatin', 'medication_name' => 'Simvastatin', 'test_code' => 'alt', 'expected_effect' => 'elevates', 'severity' => 'mild', 'clinician_note' => 'Statins can cause mild ALT elevation.'],
            ['medication_slug' => 'simvastatin', 'medication_name' => 'Simvastatin', 'test_code' => 'ldl', 'expected_effect' => 'lowers', 'severity' => 'significant', 'clinician_note' => 'Expected significant LDL reduction.'],

            // ── Pain / Anti-inflammatory ──
            ['medication_slug' => 'ibuprofen', 'medication_name' => 'Ibuprofen', 'test_code' => 'creatinine', 'expected_effect' => 'elevates', 'severity' => 'moderate', 'clinician_note' => 'NSAID — can reduce renal blood flow, increasing creatinine.'],
            ['medication_slug' => 'diclofenac', 'medication_name' => 'Diclofenac', 'test_code' => 'creatinine', 'expected_effect' => 'elevates', 'severity' => 'moderate', 'clinician_note' => 'NSAID — can reduce renal blood flow.'],
            ['medication_slug' => 'diclofenac', 'medication_name' => 'Diclofenac', 'test_code' => 'alt', 'expected_effect' => 'elevates', 'severity' => 'mild', 'clinician_note' => 'NSAIDs can cause mild hepatotoxicity.'],
            ['medication_slug' => 'paracetamol', 'medication_name' => 'Paracetamol', 'test_code' => 'alt', 'expected_effect' => 'elevates', 'severity' => 'mild', 'clinician_note' => 'At therapeutic doses, effect is usually minimal. Toxic doses cause severe elevation.'],

            // ── Anticoagulants ──
            ['medication_slug' => 'warfarin', 'medication_name' => 'Warfarin', 'test_code' => 'inr', 'expected_effect' => 'elevates', 'severity' => 'significant', 'clinician_note' => 'Warfarin is taken to raise INR. Therapeutic range typically 2.0–3.0.'],
            ['medication_slug' => 'warfarin', 'medication_name' => 'Warfarin', 'test_code' => 'pt', 'expected_effect' => 'elevates', 'severity' => 'significant', 'clinician_note' => 'Expected prolongation of prothrombin time.'],
        ];

        foreach ($effects as $data) {
            DB::table('medication_effects')->insertOrIgnore($data);
        }

        $this->command?->info('Seeded ' . count($effects) . ' medication effect entries.');
    }
}