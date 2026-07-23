<?php

namespace App\Services;

use App\Models\AiInterpretation;
use Illuminate\Support\Facades\Http;

class DeepSeekService
{
    /**
     * Send flagged results to DeepSeek for plain-language interpretation.
     * Graceful degradation: returns null on failure so the controller can fall back to flags-only.
     */
    public function interpret(AiInterpretation $interpretation, array $flags): ?string
    {
        $apiKey = \App\Models\Setting::getValue('api.deepseek_key') ?: config('services.deepseek.api_key');

        if (empty($apiKey)) {
            $interpretation->update([
                'status' => 'failed',
                'error_message' => 'DeepSeek API key not configured.',
            ]);
            return null;
        }

        $prompt = $interpretation->prompt_input;
        $model = \App\Models\Setting::getValue('api.deepseek_model') ?: config('services.deepseek.model', 'deepseek-chat');
        $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($baseUrl . '/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => (int) (\App\Models\Setting::getValue('api.deepseek_max_tokens') ?: config('services.deepseek.max_tokens', 2048)),
                'temperature' => (float) (\App\Models\Setting::getValue('api.deepseek_temperature') ?: config('services.deepseek.temperature', 0.3)),
            ]);

            if (!$response->successful()) {
                $interpretation->update([
                    'status' => 'failed',
                    'error_message' => 'DeepSeek API error: ' . ($response->json('error.message') ?? $response->status()),
                ]);
                return null;
            }

            $body = $response->json();
            $text = $body['choices'][0]['message']['content'] ?? null;

            if (!$text) {
                $interpretation->update([
                    'status' => 'failed',
                    'error_message' => 'Empty response from DeepSeek.',
                ]);
                return null;
            }

            $interpretation->update([
                'llm_output' => $body,
                'interpretation_text' => $text,
                'model_used' => $model,
                'status' => 'completed',
                'generated_at' => now(),
            ]);

            return $text;
        } catch (\Throwable $e) {
            $interpretation->update([
                'status' => 'failed',
                'error_message' => 'DeepSeek connection error: ' . $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Interpret raw PDF text — used for uploaded lab reports.
     */
    public function interpretPdf(AiInterpretation $interpretation, string $pdfText): ?string
    {
        $apiKey = config('services.deepseek.api_key');

        if (empty($apiKey)) {
            $interpretation->update([
                'status' => 'failed',
                'error_message' => 'DeepSeek API key not configured.',
            ]);
            return null;
        }

        $prompt = "Here is the raw text extracted from a lab report PDF. Analyze it and provide a patient-friendly interpretation:\n\n" . $pdfText;
        $model = config('services.deepseek.model', 'deepseek-chat');
        $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($baseUrl . '/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $this->pdfSystemPrompt()],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => (int) config('services.deepseek.max_tokens', 4096),
                'temperature' => (float) config('services.deepseek.temperature', 0.3),
            ]);

            if (!$response->successful()) {
                $interpretation->update([
                    'status' => 'failed',
                    'error_message' => 'DeepSeek API error: ' . ($response->json('error.message') ?? $response->status()),
                ]);
                return null;
            }

            $body = $response->json();
            $text = $body['choices'][0]['message']['content'] ?? null;

            if (!$text) {
                $interpretation->update([
                    'status' => 'failed',
                    'error_message' => 'Empty response from DeepSeek.',
                ]);
                return null;
            }

            $interpretation->update([
                'llm_output' => $body,
                'interpretation_text' => $text,
                'model_used' => $model,
                'status' => 'completed',
                'generated_at' => now(),
            ]);

            return $text;
        } catch (\Throwable $e) {
            $interpretation->update([
                'status' => 'failed',
                'error_message' => 'DeepSeek connection error: ' . $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Strict system prompt with guardrails.
     */
    private function systemPrompt(): string
    {
        return <<<'TXT'
You are a clinical lab result interpreter for LabDoc, a Nigerian health-tech platform. Your job is to explain lab results in the simplest possible language — like you're talking to a friend who knows nothing about medicine.

IMPORTANT GUARDRAILS — follow these strictly:
1. NEVER claim to diagnose a disease or condition. Use "may indicate" or "could suggest."
2. NEVER recommend medications or dosages.
3. ALWAYS include: "This is NOT medical advice. Please consult a licensed healthcare professional."

FORMAT — Keep it SHORT and SCANNABLE:
## ⚠️ Abnormal Results
(If any values are flagged abnormal or critical, list them FIRST with a clear callout. Use emoji ⚠️ for critical, 🔸 for high/low.)
Format each as: **Test Name**: value (normal range: X-Y) — ⚠️ What this means in 1 simple sentence.

## ✅ Normal Results
Quick one-liner: "All other tested values were within normal ranges." or list them briefly if only a few.

## 💡 What This Means
2-3 plain-language sentences connecting the dots. If everything is normal, say so clearly. If something needs attention, say why.

## 📋 Next Steps
1-2 simple suggestions (e.g., "Discuss with your doctor," "Repeat in 3 months," "Monitor your diet").

Use grade 7-8 English. No jargon. Every medical term must be explained in parentheses.
TXT;
    }

    /**
     * System prompt for PDF lab report interpretation.
     */
    private function pdfSystemPrompt(): string
    {
        return <<<'TXT'
You are a clinical lab report interpreter for LabDoc, a Nigerian health-tech platform. Your job is to read raw text extracted from uploaded lab report PDFs and produce a clear, plain-language summary that a non-medical person can understand.

IMPORTANT GUARDRAILS — follow these strictly:
1. NEVER claim to diagnose a disease or condition. Say "this may indicate" or "this is consistent with" instead of "you have."
2. NEVER recommend specific medications or dosages. If the report mentions medications, you may state "The report indicates X was recommended" but never prescribe.
3. ALWAYS include: "This is NOT medical advice. Please consult a licensed healthcare professional for proper diagnosis and treatment."
4. If any finding suggests an emergency (sepsis indicators, critical organ failure markers, etc.), explicitly state: "This report contains findings that may require urgent medical attention. Please seek immediate care."
5. Use plain, accessible language (Flesch-Kincaid grade 8–10). Explain medical terms in simple words.
6. Structure your response with clear sections.

RESPONSE FORMAT — Be CONCISE and SCANNABLE:

## ⚠️ Key Findings
- If anything is abnormal or notable, list it FIRST with ⚠️ for critical or 🔸 for elevated/low.
- Format: **Test Name**: value (normal: X-Y) — What this means in 1 short sentence.
- If everything is normal, just say: "All results are within normal ranges. ✅"

## 💡 Simple Explanation
2-3 plain sentences connecting any findings together. Explain in grade 7 English. No medical jargon.

## 📋 What To Do
1-2 practical next steps. Do NOT prescribe medications.

## ℹ️ Disclaimer
"This is NOT medical advice. Please consult a licensed healthcare professional."
TXT;
    }
}
