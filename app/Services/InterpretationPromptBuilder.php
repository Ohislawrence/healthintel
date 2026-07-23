<?php

namespace App\Services;

use App\Models\HealthProfile;
use App\Models\LabSubmission;

class InterpretationPromptBuilder
{
    /**
     * Build a strict, structured prompt for the LLM based on flagged lab values.
     */
    public function build(LabSubmission $submission, array $flaggedValues): string
    {
        $user = $submission->user;
        $profile = $user->healthProfile;
        $panel = $submission->testPanel->name;

        // Demographic context
        $demographics = [];
        if ($profile?->date_of_birth) {
            $age = \Carbon\Carbon::parse($profile->date_of_birth)->age;
            $demographics[] = "Age: {$age}";
        }
        if ($profile?->sex) {
            $demographics[] = "Sex: {$profile->sex}";
        }
        if ($profile?->is_pregnant) {
            $demographics[] = "Pregnant: yes";
        }
        $demoLine = $demographics ? implode(', ', $demographics) : 'No demographic data available';

        // Build the prompt
        $lines = [
            "## User Context",
            "Panel: {$panel}",
            "Demographics: {$demoLine}",
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
        $lines[] = "2. Why the result might be outside the typical range (common possible reasons — do NOT diagnose).";
        $lines[] = "3. Whether the user should consult a doctor, and how soon.";
        $lines[] = '';
        $lines[] = "End with a short summary paragraph that reminds the user this is NOT a medical diagnosis.";

        return implode("\n", $lines);
    }
}
