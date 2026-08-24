<?php

namespace App\Services;

use App\Models\PdfSubmissionDraft;
use Illuminate\Support\Facades\Storage;

class ImageLabReportService
{
    public function __construct(
        private DeepSeekService $deepSeek,
        private PdfValueExtractor $pdfValueExtractor,
    ) {}

    /**
     * Process an image-based lab report (camera photo or uploaded image).
     * Uses DeepSeek vision capabilities to extract test values from images.
     *
     * @param string $imageBase64 Base64-encoded image data
     * @param int $userId
     * @param string|null $imageName Optional file name
     * @return array{success: bool, draft_id?: int, extracted_tests?: array, message?: string, error?: string}
     */
    public function processImage(string $imageBase64, int $userId, ?string $imageName = null): array
    {
        // Preprocess: validate and optimize the image
        $processedImage = $this->preprocessImage($imageBase64);
        if (!$processedImage) {
            return ['success' => false, 'error' => 'Invalid or corrupted image data.'];
        }

        // Save the image for record-keeping
        $fileName = ($imageName ?? 'lab-report') . '_' . time() . '.jpg';
        $path = 'lab-reports/images/' . $fileName;
        Storage::put($path, base64_decode($processedImage));

        // Send to DeepSeek vision for text extraction
        $extractedJson = $this->extractValuesWithVision($imageBase64);

        // Parse the extracted JSON
        $extractedTests = $this->parseVisionResponse($extractedJson);

        if (empty($extractedTests)) {
            // Fallback: try traditional OCR approach
            return [
                'success' => false,
                'error' => 'Could not extract test values from this image. Please try taking a clearer photo, or enter values manually.',
            ];
        }

        // Save draft for user confirmation
        $draft = PdfSubmissionDraft::create([
            'user_id' => $userId,
            'raw_ocr_text' => json_encode($extractedJson),
            'extracted_tests' => $extractedTests,
            'confirmation_status' => 'pending',
            'pdf_path' => $path,
        ]);

        return [
            'success' => true,
            'draft_id' => $draft->id,
            'extracted_tests' => $extractedTests,
            'message' => 'We found ' . count($extractedTests) . ' test values. Please review and confirm before interpretation.',
        ];
    }

    /**
     * Preprocess image: validate format, optimize size, ensure quality.
     */
    private function preprocessImage(string $imageBase64): ?string
    {
        // Strip data URI prefix if present (e.g., "data:image/jpeg;base64,")
        $cleanBase64 = preg_replace('#^data:image/\w+;base64,#', '', $imageBase64);
        $imageData = base64_decode($cleanBase64);

        if (!$imageData || strlen($imageData) < 100) {
            return null;
        }

        // Check if it's a valid image by examining magic bytes
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);

        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
            return null;
        }

        // For DeepSeek vision, we need the image to be under ~20MB after base64 encoding
        // but also not too large. Resize if needed using GD.
        if (strlen($imageData) > 10 * 1024 * 1024) { // > 10MB raw
            try {
                $img = @imagecreatefromstring($imageData);
                if ($img) {
                    $originalWidth = imagesx($img);
                    $originalHeight = imagesy($img);
                    $maxDimension = 2048;

                    if ($originalWidth > $maxDimension || $originalHeight > $maxDimension) {
                        $ratio = min($maxDimension / $originalWidth, $maxDimension / $originalHeight);
                        $newWidth = (int) ($originalWidth * $ratio);
                        $newHeight = (int) ($originalHeight * $ratio);

                        $resized = imagecreatetruecolor($newWidth, $newHeight);
                        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

                        ob_start();
                        imagejpeg($resized, null, 85);
                        $imageData = ob_get_clean();

                        imagedestroy($img);
                        imagedestroy($resized);

                        return base64_encode($imageData);
                    }

                    imagedestroy($img);
                }
            } catch (\Throwable) {
                // If resize fails, try using original
            }
        }

        return $cleanBase64;
    }

    /**
     * Send image to DeepSeek vision API to extract lab test values.
     */
    private function extractValuesWithVision(string $imageBase64): ?string
    {
        $apiKey = config('services.deepseek.api_key')
            ?: ($_ENV['DEEPSEEK_API_KEY'] ?? getenv('DEEPSEEK_API_KEY'))
            ?: null;

        if (empty($apiKey)) {
            return null;
        }

        $model = config('services.deepseek.vision_model')
            ?: config('services.deepseek.model')
            ?: 'deepseek-chat';
        $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

        // Strip data URI prefix for API
        $cleanBase64 = preg_replace('#^data:image/\w+;base64,#', '', $imageBase64);

        $systemPrompt = <<<'TXT'
You are a medical lab report data extractor. Your job is to look at images of lab reports and extract ALL test names, values, and units.

CRITICAL: If the image is NOT a lab report (e.g., it's a receipt, ID card, letter, selfie, screenshot, or anything else), return EXACTLY: {"error": "NOT_A_LAB_REPORT"}

IMPORTANT: Return ONLY valid JSON. No explanations, no markdown wrapping.

Format:
[
  {"test_name": "Glucose (Fasting)", "value": 118, "unit": "mg/dL"},
  {"test_name": "Hemoglobin", "value": 13.5, "unit": "g/dL"}
]

Rules:
1. If the image is not a lab report, return: []
2. Extract EVERY test you can see — errors of inclusion are better than omission
3. Keep numeric values as numbers (not strings)
4. If a unit is missing, use "" (empty string)
5. If you see reference ranges listed, include them in the output as: {"test_name": "...", "value": ..., "unit": "...", "ref_range": "70-99"}
TXT;

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(45)->post($baseUrl . '/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Extract all lab test values from this report image. Return ONLY JSON array.',
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:image/jpeg;base64,' . $cleanBase64,
                                    'detail' => 'high',
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 2048,
                'temperature' => 0.1,
            ]);

            if ($response->successful()) {
                $body = $response->json();
                return $body['choices'][0]['message']['content'] ?? null;
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Parse the vision API response into structured test data.
     */
    private function parseVisionResponse(?string $rawResponse): array
    {
        if (!$rawResponse) {
            return [];
        }

        // Try to extract JSON from the response
        $json = $rawResponse;

        // Remove markdown code block wrappers if present
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?```/s', $rawResponse, $matches)) {
            $json = $matches[1];
        }

        $parsed = json_decode($json, true);

        if (!is_array($parsed)) {
            // Try to find JSON array brackets
            if (preg_match('/\[\s*\{.*\}\s*\]/s', $rawResponse, $matches)) {
                $parsed = json_decode($matches[0], true);
            }
        }

        if (!is_array($parsed)) {
            return [];
        }

        // Normalize the extracted data
        $normalized = [];
        $seenNames = [];
        foreach ($parsed as $item) {
            if (!is_array($item) || empty($item['test_name'])) {
                continue;
            }

            $testName = trim($item['test_name']);

            // Skip exact duplicate test names (case-insensitive) so the review
            // screen never shows the same test more than once.
            $seenKey = mb_strtolower($testName);
            if (isset($seenNames[$seenKey])) {
                continue;
            }
            $seenNames[$seenKey] = true;

            $normalized[] = [
                'test_name' => $testName,
                'value' => is_numeric($item['value'] ?? null) ? (float) ($item['value']) : ($item['value'] ?? ''),
                'unit' => trim($item['unit'] ?? '' ?? ''),
                'ref_range' => trim($item['ref_range'] ?? $item['reference_range'] ?? ''),
            ];
        }

        return $normalized;
    }
}