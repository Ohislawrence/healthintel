<?php

namespace App\Services;

use App\Models\Symptom;

class SymptomPromptBuilder
{
    /**
     * Build a prompt for the LLM to suggest relevant test panels based on symptoms.
     */
    public function build(array $selectedSymptoms, string $patientContext): string
    {
        $names = collect($selectedSymptoms)->pluck('name')->implode(', ');

        return <<<TXT
## Symptom Check

**Reported symptoms:** {$names}

**Patient context:** {$patientContext}

## Instructions
You are LabDoc's clinical triage assistant.

1. For each symptom listed, explain in 1-2 plain-language sentences why someone might experience it (common possible causes — do NOT diagnose).
2. Suggest 1-3 lab test panels from this list that could help a clinician investigate these symptoms:
   - Full Blood Count (FBC)
   - Lipid Profile
   - Liver Function Test (LFT)
   - Renal Function Test
   - Diabetic Panel
   - Thyroid Function Test
   - Iron Studies
3. Include a disclaimer: "This is NOT medical advice. Lab tests should be ordered and interpreted by a licensed healthcare professional."
4. If any symptom is potentially urgent (e.g., chest pain, severe headache, difficulty breathing), explicitly recommend seeking immediate medical attention.
TXT;
    }
}
