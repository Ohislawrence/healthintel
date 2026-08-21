<?php

namespace App\Services;

use App\Models\AiInterpretation;
use Illuminate\Support\Facades\Http;

class DeepSeekService
{
    private function credentials(): array
    {
        $apiKey = config('services.deepseek.api_key')
            ?: ($_ENV['DEEPSEEK_API_KEY'] ?? getenv('DEEPSEEK_API_KEY'))
            ?: null;
        $model = config('services.deepseek.model')
            ?: ($_ENV['DEEPSEEK_MODEL'] ?? getenv('DEEPSEEK_MODEL'))
            ?: 'deepseek-chat';
        $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');
        return compact('apiKey', 'model', 'baseUrl');
    }

    public function ask(string $prompt, int $maxTokens = 200, float $temperature = 0.3, ?string $systemPrompt = null): ?string
    {
        $apiKey = config('services.deepseek.api_key')
            ?: ($_ENV['DEEPSEEK_API_KEY'] ?? getenv('DEEPSEEK_API_KEY'))
            ?: null;
        if (empty($apiKey)) return null;

        $model = config('services.deepseek.model')
            ?: ($_ENV['DEEPSEEK_MODEL'] ?? getenv('DEEPSEEK_MODEL'))
            ?: 'deepseek-chat';
        $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($baseUrl . '/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt ?? $this->systemPrompt()],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);
            if ($response->successful()) {
                $body = $response->json();
                return $body['choices'][0]['message']['content'] ?? null;
            }
            return null;
        } catch (\Throwable) { return null; }
    }

    public function interpret(AiInterpretation $interpretation, array $flags): ?string
    {
        $apiKey = config('services.deepseek.api_key')
            ?: ($_ENV['DEEPSEEK_API_KEY'] ?? getenv('DEEPSEEK_API_KEY'))
            ?: $this->readEnvFile('DEEPSEEK_API_KEY')
            ?: null;
        if (empty($apiKey)) { $interpretation->update(['status' => 'failed', 'error_message' => 'DeepSeek API key not configured.']); return null; }

        $prompt = $interpretation->prompt_input;
        $model = config('services.deepseek.model') ?: ($_ENV['DEEPSEEK_MODEL'] ?? getenv('DEEPSEEK_MODEL')) ?: 'deepseek-v4-flash';
        $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey, 'Content-Type' => 'application/json',
            ])->timeout(30)->post($baseUrl . '/v1/chat/completions', [
                'model' => $model,
                'messages' => [['role' => 'system', 'content' => $this->systemPrompt()], ['role' => 'user', 'content' => $prompt]],
                'max_tokens' => (int) config('services.deepseek.max_tokens', 2048),
                'temperature' => (float) config('services.deepseek.temperature', 0.3),
            ]);
            if (!$response->successful()) {
                $interpretation->update(['status' => 'failed', 'error_message' => 'DeepSeek API error: ' . ($response->json('error.message') ?? $response->status())]);
                return null;
            }
            $body = $response->json();
            $text = $body['choices'][0]['message']['content'] ?? null;
            if (!$text) { $interpretation->update(['status' => 'failed', 'error_message' => 'Empty response from DeepSeek.']); return null; }
            $text = $this->cleanUtf8($text);
            $interpretation->update(['llm_output' => $this->sanitizeUtf8Recursively($body), 'interpretation_text' => $text, 'model_used' => $model, 'status' => 'completed', 'generated_at' => now()]);
            return $text;
        } catch (\Throwable $e) {
            $interpretation->update(['status' => 'failed', 'error_message' => 'DeepSeek connection error: ' . $e->getMessage()]);
            return null;
        }
    }

    public function interpretPdf(AiInterpretation $interpretation, string $pdfText): ?string
    {
        $apiKey = config('services.deepseek.api_key') ?: ($_ENV['DEEPSEEK_API_KEY'] ?? getenv('DEEPSEEK_API_KEY')) ?: null;
        if (empty($apiKey)) { $interpretation->update(['status' => 'failed', 'error_message' => 'DeepSeek API key not configured.']); return null; }

        $prompt = "Here is the raw text extracted from a lab report PDF. Analyze it and provide a patient-friendly interpretation:\n\n" . $pdfText;
        $model = config('services.deepseek.model') ?: ($_ENV['DEEPSEEK_MODEL'] ?? getenv('DEEPSEEK_MODEL')) ?: 'deepseek-v4-flash';
        $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey, 'Content-Type' => 'application/json',
            ])->timeout(60)->post($baseUrl . '/v1/chat/completions', [
                'model' => $model,
                'messages' => [['role' => 'system', 'content' => $this->pdfSystemPrompt()], ['role' => 'user', 'content' => $prompt]],
                'max_tokens' => (int) config('services.deepseek.max_tokens', 4096),
                'temperature' => (float) config('services.deepseek.temperature', 0.3),
            ]);
            if (!$response->successful()) {
                $interpretation->update(['status' => 'failed', 'error_message' => 'DeepSeek API error: ' . ($response->json('error.message') ?? $response->status())]);
                return null;
            }
            $body = $response->json();
            $text = $body['choices'][0]['message']['content'] ?? null;
            if (!$text) { $interpretation->update(['status' => 'failed', 'error_message' => 'Empty response from DeepSeek.']); return null; }
            $text = $this->cleanUtf8($text);
            $interpretation->update(['llm_output' => $this->sanitizeUtf8Recursively($body), 'interpretation_text' => $text, 'model_used' => $model, 'status' => 'completed', 'generated_at' => now()]);
            return $text;
        } catch (\Throwable $e) {
            $interpretation->update(['status' => 'failed', 'error_message' => 'DeepSeek connection error: ' . $e->getMessage()]);
            return null;
        }
    }

    /**
     * Interpret a narrative clinical report — e.g. radiology, imaging scans
     * (ultrasound, CT, MRI, X-ray, Doppler), ECG/echocardiogram, and other
     * findings that are expressed as prose rather than numeric lab tables.
     */
    public function interpretNarrative(AiInterpretation $interpretation, string $reportText): ?string
    {
        $apiKey = config('services.deepseek.api_key') ?: ($_ENV['DEEPSEEK_API_KEY'] ?? getenv('DEEPSEEK_API_KEY')) ?: null;
        if (empty($apiKey)) { $interpretation->update(['status' => 'failed', 'error_message' => 'DeepSeek API key not configured.']); return null; }

        $model = config('services.deepseek.model') ?: ($_ENV['DEEPSEEK_MODEL'] ?? getenv('DEEPSEEK_MODEL')) ?: 'deepseek-v4-flash';
        $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey, 'Content-Type' => 'application/json',
            ])->timeout(60)->post($baseUrl . '/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $this->narrativeSystemPrompt()],
                    ['role' => 'user', 'content' => "Explain the following medical report in plain language:\n\n" . $reportText],
                ],
                'max_tokens' => (int) config('services.deepseek.max_tokens', 4096),
                'temperature' => (float) config('services.deepseek.temperature', 0.3),
            ]);
            if (!$response->successful()) {
                $interpretation->update(['status' => 'failed', 'error_message' => 'DeepSeek API error: ' . ($response->json('error.message') ?? $response->status())]);
                return null;
            }
            $body = $response->json();
            $text = $body['choices'][0]['message']['content'] ?? null;
            if (!$text) { $interpretation->update(['status' => 'failed', 'error_message' => 'Empty response from DeepSeek.']); return null; }
            $text = $this->cleanUtf8($text);
            $interpretation->update(['llm_output' => $this->sanitizeUtf8Recursively($body), 'interpretation_text' => $text, 'model_used' => $model, 'status' => 'completed', 'generated_at' => now()]);
            return $text;
        } catch (\Throwable $e) {
            $interpretation->update(['status' => 'failed', 'error_message' => 'DeepSeek connection error: ' . $e->getMessage()]);
            return null;
        }
    }

    public function streamInterpret(string $prompt, string $systemPrompt = null): \Generator
    {
        $creds = $this->credentials();
        if (empty($creds['apiKey'])) { yield "Error: DeepSeek API key not configured."; return; }
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 60, 'headers' => ['Authorization' => 'Bearer ' . $creds['apiKey'], 'Content-Type' => 'application/json']]);
            $response = $client->post($creds['baseUrl'] . '/v1/chat/completions', [
                'json' => ['model' => $creds['model'], 'messages' => [['role' => 'system', 'content' => $systemPrompt ?? $this->systemPrompt()], ['role' => 'user', 'content' => $prompt]], 'max_tokens' => (int) config('services.deepseek.max_tokens', 2048), 'temperature' => (float) config('services.deepseek.temperature', 0.3), 'stream' => true],
                'stream' => true,
            ]);
            $body = $response->getBody(); $buffer = '';
            while (!$body->eof()) {
                $buffer .= $body->read(1024);
                $lines = explode("\n", $buffer); $buffer = array_pop($lines) ?? '';
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || $line === 'data: [DONE]') continue;
                    if (str_starts_with($line, 'data: ')) {
                        $json = json_decode(substr($line, 6), true);
                        $content = $json['choices'][0]['delta']['content'] ?? null;
                        if ($content) yield $content;
                    }
                }
            }
        } catch (\Throwable $e) { yield "Error: " . $e->getMessage(); }
    }

    public function chatAboutResult(array $conversationHistory, string $newMessage): ?string
    {
        $creds = $this->credentials();
        if (empty($creds['apiKey'])) return null;

        $chatPrompt = <<<'TXT'
You are a helpful health assistant answering a patient's questions about their lab results.
CRITICAL: Be BRIEF. Keep every response to 2-4 short sentences or 3-5 bullet points maximum.
• NEVER diagnose disease. Use "may indicate" or "could suggest."
• NEVER recommend medications.
• ALWAYS end with: "This is NOT medical advice. Please consult your doctor."
• If symptoms sound urgent (chest pain, severe bleeding), say: "Please seek emergency care immediately."
• Answer in plain Grade 7-8 English.
• Be conversational and concise — like texting a friend.
TXT;

        $messages = [['role' => 'system', 'content' => $chatPrompt]];
        foreach ($conversationHistory as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $newMessage];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $creds['apiKey'],
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($creds['baseUrl'] . '/v1/chat/completions', [
                'model' => $creds['model'],
                'messages' => $messages,
                'max_tokens' => 200,
                'temperature' => 0.5,
            ]);
            if ($response->successful()) {
                $body = $response->json();
                return $body['choices'][0]['message']['content'] ?? null;
            }
            return null;
        } catch (\Throwable) { return null; }
    }

    /**
     * Sanitize a string so it contains only valid UTF-8, preventing JSON
     * encoding failures and DB column encoding errors.
     */
    private function cleanUtf8(string $value): string
    {
        $cleaned = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        return $cleaned === false ? '' : $cleaned;
    }

    /**
     * Recursively sanitize all string values in an array so the `llm_output`
     * JSON column never contains invalid UTF-8 (which crashes JSON responses).
     */
    private function sanitizeUtf8Recursively(mixed $data): mixed
    {
        if (is_string($data)) {
            return $this->cleanUtf8($data);
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeUtf8Recursively($value);
            }
            return $data;
        }
        return $data;
    }

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

    private function narrativeSystemPrompt(): string
    {
        return <<<'TXT'
You are a medical report interpreter for LabDoc, a Nigerian health-tech platform. Your job is to take the raw text of a clinical report — radiographs, scans and clinical notes such as ultrasound, CT, MRI, X-ray, Doppler, ECG/echocardiogram, obstetric, or pathology reports — and explain it in the plainest possible language so a non-medical person fully understands it.

CRITICAL FIRST CHECK — Validate the content:
- If the text is clearly NOT any kind of medical or health report (e.g. a receipt, a letter, an invoice, a form, or unrelated text), respond with EXACTLY: "NOT_A_MEDICAL_DOCUMENT"
- Otherwise, ALWAYS interpret it. These reports are primarily narrative prose, so do NOT reject them just because they lack a table of numeric lab values.

IMPORTANT GUARDRAILS — follow these strictly:
1. NEVER claim to diagnose a disease or condition definitively. Use phrases like "this may indicate," "this is consistent with," or "the report notes."
2. NEVER recommend medications or dosages.
3. ALWAYS include: "This is NOT medical advice. Please consult a licensed healthcare professional for proper diagnosis and treatment."
4. If the report mentions anything that could be urgent (e.g. "marked stenosis", "effusion", "swelling", "mass", "fracture", "possible malignancy", or severe BP), explicitly add: "Some findings may need urgent medical attention. Please see a doctor soon."
5. Use VERY simple, everyday English (grade 5-6, like you are talking to a friend with no medical knowledge). Explain EVERY medical term in plain words the first time you use it (e.g. "stenosis (narrowing)"). Avoid jargon completely.

RESPONSE FORMAT — Be warm, clear and scannable using markdown:

## 🩺 What This Report Is
One short sentence identifying the type of scan/report and the body area.

## ⚠️ Key Findings
- List any abnormal or notable findings FIRST, each as a bolded short phrase, followed by a one-sentence plain-language explanation of what it means.
- If everything is normal, say: "The report shows no significant abnormalities. ✅"

## ✅ Normal Findings
A brief line summarizing the normal parts (so the user is reassured).

## 💡 What This Means (Simple Explanation)
2-4 plain sentences connecting the findings together in everyday words.

## 📋 Next Steps
1-2 practical suggestions only (e.g. "Discuss with your doctor," "Repeat the scan in a few months," "Make follow-up lifestyle changes"). Do NOT prescribe medications.

## ℹ️ Disclaimer
"This is NOT medical advice. Please consult a licensed healthcare professional."
TXT;
    }

    private function pdfSystemPrompt(): string
    {
        return <<<'TXT'
You are a clinical lab report interpreter for LabDoc, a Nigerian health-tech platform. Your job is to read raw text extracted from uploaded lab report PDFs and produce a clear, plain-language summary that a non-medical person can understand.

CRITICAL FIRST CHECK — Before interpreting, validate the content:
- If the text does NOT contain recognizable lab tests (no test names, no numeric values with units like mg/dL, mmol/L, g/dL, U/L, etc.), respond with EXACTLY this message: "⚠️ NOT_A_LAB_REPORT"
- Do NOT try to interpret non-lab content. If it's a receipt, letter, form, or any non-medical document, return the error message above.
- Only proceed with interpretation if you can identify at least 2-3 recognizable lab tests with values.

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