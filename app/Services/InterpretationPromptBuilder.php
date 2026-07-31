<?php

namespace App\Services;

use App\Models\HealthProfile;
use App\Models\LabSubmission;

class InterpretationPromptBuilder
{
    /**
     * Build a strict, structured prompt for the LLM based on flagged lab values.
     * Now includes full health profile context: conditions, medications, BMI.
     */
    public function build(LabSubmission $submission, array $flaggedValues): string
    {
        $user = $submission->user;
        $profile = $user->healthProfile;
        $panel = $submission->testPanel->name;

        // Demographic context
        $demographics = [];
        if ($profile?->date_of_birth) {
            $demographics[] = 'Age: ' . \Carbon\Carbon::parse($profile->date_of_birth)->age . ' years';
        }
        if ($profile?->sex) {
            $demographics[] = 'Sex: ' . $profile->sex;
        }
        if ($profile?->is_pregnant) {
            $demographics[] = 'Pregnant: yes';
        }
        if ($profile?->height_cm && $profile?->weight_kg) {
            $bmi = round($profile->weight_kg / (($profile->height_cm / 100) ** 2), 1);
            $demographics[] = "BMI: {$bmi} kg/m²";
        }
        $demoLine = $demographics ? implode(', ', $demographics) : 'No demographic data available';

        // Medical conditions context
        $conditionsLine = 'No known medical conditions.';
        if ($profile?->medical_conditions) {
            $conditions = $profile->medical_conditions;
            if (is_string($conditions)) {
                $decoded = json_decode($conditions, true);
                if (is_array($decoded)) $conditions = $decoded;
            }
            if (is_array($conditions) && !empty($conditions)) {
                $conditionList = is_array(reset($conditions))
                    ? array_column($conditions, 'condition')
                    : $conditions;
                $conditionsLine = 'Known conditions: ' . implode(', ', $conditionList);
            }
        }

        // Medications context
        $medicationsLine = 'No current medications reported.';
        if ($profile?->current_medications) {
            $meds = $profile->current_medications;
            if (is_string($meds)) {
                $decoded = json_decode($meds, true);
                if (is_array($decoded)) $meds = $decoded;
            }
            if (is_array($meds) && !empty($meds)) {
                $medList = is_array(reset($meds))
                    ? array_column($meds, 'medication')
                    : $meds;
                $medicationsLine = 'Current medications: ' . implode(', ', $medList);
            }
        }

        // Profile completeness note
        $profileNote = '';
        if (!$profile || !$profile->profile_completed) {
            $profileNote = "\nNote: The patient's health profile is incomplete. For more personalized interpretation, complete your health profile.\n";
        }

        // Build the prompt
        $lines = [
            "## User Context",
            "Panel: {$panel}",
            "Demographics: {$demoLine}",
            "Medical History: {$conditionsLine}",
            "Medications: {$medicationsLine}",
            $profileNote,
            "",
            "## Lab Results & Flags",
            "",
        ];

        foreach ($flaggedValues as $fv) {
            $lines[] = "- {$fv['test_name']}: {$fv['value']} {$fv['unit']} " .
                "(typical range: {$fv['range_low']}–{$fv['range_high']} {$fv['unit']}) " .
                "— FLAG: {$fv['flag']}" .
                ($fv['is_critical'] ? ' [CRITICAL – urgent attention needed]' : '');
        }

        $lines[] = '';
        $lines[] = "## Instructions";
        $lines[] = "For each flagged test above, explain in plain language:";
        $lines[] = "1. What this test measures.";
        $lines[] = "2. Why the result might be outside the typical range — consider the patient's known conditions and medications in your reasoning.";
        $lines[] = "3. Whether the user should consult a doctor, and how soon.";
        $lines[] = '';
        $lines[] = "If the patient has a known condition relevant to these results, frame the interpretation in that context (e.g., elevated HbA1c in a known diabetic is management feedback, not a new finding).";
        $lines[] = '';
        $lines[] = "End with a short summary paragraph that reminds the user this is NOT a medical diagnosis.";

        return implode("\n", $lines);
    }
}
