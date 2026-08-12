<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Models\AiInterpretation;
use App\Models\LabSubmission;
use App\Models\LabSubmissionValue;
use App\Models\TestPanel;
use App\Models\TestReferenceRange;
use App\Services\CreditService;
use App\Services\DeepSeekService;
use App\Services\InterpretationPromptBuilder;
use App\Services\ReferenceRangeEngine;
use App\Services\ReferenceRangeService;
use Illuminate\Http\Request;
use Spatie\PdfToText\Pdf;
use Illuminate\Support\Facades\Storage;

class LabSubmissionController extends BaseController
{
    public function __construct(
        private ReferenceRangeEngine $flagEngine,
        private InterpretationPromptBuilder $promptBuilder,
        private DeepSeekService $deepSeek,
        private CreditService $creditService,
        private ReferenceRangeService $referenceRangeService,
    ) {}

    public function panels()
    {
        $panels = TestPanel::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        return $this->success(['panels' => $panels]);
    }

    public function panelShow(string $slug)
    {
        $panel = TestPanel::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return $this->success(['panel' => $panel->load('ranges')]);
    }

    public function index(Request $request)
    {
        $submissions = $request->user()
            ->labSubmissions()
            ->with(['testPanel:id,name', 'values', 'interpretation'])
            ->latest('submitted_at')
            ->paginate(20);
        return $this->paginated($submissions);
    }

    public function show(int $id, Request $request)
    {
        $submission = $request->user()
            ->labSubmissions()
            ->with(['testPanel', 'values', 'interpretation'])
            ->findOrFail($id);
        return $this->success(['submission' => $submission]);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'panel_slug' => 'required|string|exists:test_panels,slug',
            'values' => 'required|array|min:1',
            'values.*.test_slug' => 'required|string',
            'values.*.value' => 'required|numeric',
        ]);

        $user = $request->user();
        $panel = TestPanel::where('slug', $validated['panel_slug'])->firstOrFail();
        $profile = $user->healthProfile;
        $cost = config('credits.costs.lab_interpretation', 2);

        $isFirstFree = false;
        if (!$user->received_free_interpretation && config('credits.first_interpretation_free', true)) {
            $user->update(['received_free_interpretation' => true]);
            $isFirstFree = true;
        } else {
            if (!$this->creditService->hasCredits($user, $cost)) {
                return $this->error('Insufficient credits. Please top up.', 402);
            }
            $this->creditService->debit($user, $cost, 'lab_interpretation');
        }

        $effectiveCost = $isFirstFree ? 0 : $cost;

        $submission = LabSubmission::create([
            'user_id' => $user->id,
            'test_panel_id' => $panel->id,
            'credits_used' => $effectiveCost,
            'submitted_at' => now(),
        ]);

        $flaggedValues = [];
        foreach ($validated['values'] as $input) {
            $range = TestReferenceRange::where('test_slug', $input['test_slug'])
                ->where('test_panel_id', $panel->id)
                ->first();
            if (!$range) continue;

            $flag = $this->flagEngine->flag((float) $input['value'], $range, $profile);
            LabSubmissionValue::create([
                'lab_submission_id' => $submission->id,
                'test_slug' => $input['test_slug'],
                'test_name' => $range->test_name,
                'unit' => $range->unit,
                'value' => $input['value'],
                'flag' => $flag['flag'],
            ]);
            $flaggedValues[] = array_merge([
                'test_name' => $range->test_name,
                'test_slug' => $input['test_slug'],
                'value' => $input['value'],
            ], $flag);
        }

        $prompt = $this->promptBuilder->build($submission, $flaggedValues);
        $interpretation = AiInterpretation::create([
            'lab_submission_id' => $submission->id,
            'prompt_input' => $prompt,
            'guardrail_flags' => $this->buildGuardrailFlags($flaggedValues),
            'status' => 'pending',
        ]);

        $interpText = $this->deepSeek->interpret($interpretation, $flaggedValues);
        $submission->load(['values', 'interpretation']);

        return $this->success([
            'submission' => $submission,
            'flagged_values' => $flaggedValues,
            'has_interpretation' => !is_null($interpText),
        ], 'Lab results submitted', 201);
    }

    /**
     * Submit an image-based lab report (camera photo or uploaded image).
     */
    public function submitImage(Request $request)
    {
        $validated = $request->validate([
            'image_base64' => 'required|string',
            'image_name' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $imageService = app(\App\Services\ImageLabReportService::class);
        $result = $imageService->processImage(
            $validated['image_base64'],
            $user->id,
            $validated['image_name'] ?? null
        );

        if (!$result['success']) {
            return $this->error($result['error'] ?? 'Could not process this image.', 422);
        }

        return $this->success([
            'draft_id' => $result['draft_id'],
            'extracted_tests' => $result['extracted_tests'],
            'message' => $result['message'],
        ]);
    }

    /**
     * Translate an interpretation to a different language via DeepSeek.
     */
    public function translate(Request $request, int $id)
    {
        $validated = $request->validate([
            'target_language' => 'required|string|in:en,pcm,yo,ha,ig',
        ]);

        $submission = $request->user()->labSubmissions()
            ->with('interpretation')
            ->findOrFail($id);

        $originalText = $submission->interpretation?->interpretation_text;
        if (!$originalText) {
            return $this->error('No interpretation text available to translate.', 404);
        }

        $langCode = $validated['target_language'];
        if ($langCode === 'en') {
            return $this->success(['translated_text' => $originalText]);
        }

        $langService = app(\App\Services\TranslationService::class);
        $prompt = "Translate the following medical lab interpretation into another language.\n\nORIGINAL TEXT:\n{$originalText}";
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function trends(Request $request)
    {
        $validated = $request->validate(['test_slug' => 'required|string']);
        $analysis = app(\App\Services\TrendService::class)
            ->analyzeTrend($request->user()->id, $validated['test_slug']);
        return $this->success(['trend' => $analysis]);
    }

    public function shareTrend(Request $request)
    {
        $validated = $request->validate([
            'test_slug' => 'required|string',
            'delivery_method' => 'nullable|in:pdf,email',
            'recipient_email' => 'nullable|email|required_if:delivery_method,email',
        ]);

        $user = $request->user();
        $trendService = app(\App\Services\TrendService::class);
        $pdf = $trendService->generateTrendSummaryPdf($user->id, $validated['test_slug']);
        $method = $validated['delivery_method'] ?? 'pdf';

        if ($method === 'email') {
            \Mail::send([], [], function ($message) use ($validated, $pdf) {
                $message->to($validated['recipient_email'])
                    ->subject('Lab Trend Summary — Shared by LabDoc User')
                    ->html('<p>A LabDoc user has shared their lab trend summary with you. Please find it attached.</p>')
                    ->attachData($pdf, 'trend-summary.pdf', ['mime' => 'application/pdf']);
            });
            return $this->success(null, 'Trend summary sent to ' . $validated['recipient_email']);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="trend-summary.pdf"',
        ]);
    }

    public function submitPdfDraft(Request $request)
    {
        $validated = $request->validate([
            'pdf_base64' => 'required|string',
            'pdf_name' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $pdfData = base64_decode($validated['pdf_base64']);
        if (!$pdfData) return $this->error('Invalid PDF data.', 422);

        $fileName = ($validated['pdf_name'] ?? 'report') . '_' . time() . '.pdf';
        $path = 'lab-reports/' . $fileName;
        Storage::put($path, $pdfData);
        $fullPath = Storage::path($path);

        $pdfText = '';
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($fullPath);
            $pdfText = $pdf->getText();
        } catch (\Throwable) {
            return $this->error('Could not parse this PDF.', 422);
        }

        if (empty(trim($pdfText))) {
            return $this->error('No text could be extracted from this PDF.', 422);
        }

        $extractor = app(\App\Services\PdfValueExtractor::class);
        $extractedTests = $extractor->extract($pdfText);

        $draft = \App\Models\PdfSubmissionDraft::create([
            'user_id' => $user->id,
            'raw_ocr_text' => $pdfText,
            'extracted_tests' => $extractedTests,
            'confirmation_status' => 'pending',
            'pdf_path' => $path,
        ]);

        return $this->success([
            'draft_id' => $draft->id,
            'extracted_tests' => $extractedTests,
            'raw_text_preview' => substr($pdfText, 0, 500),
            'message' => count($extractedTests) > 0
                ? 'We found ' . count($extractedTests) . ' test values. Please review and confirm before interpretation.'
                : 'No test values could be automatically detected. You can manually enter them below.',
        ]);
    }

    public function confirmPdfDraft(Request $request, $draftId)
    {
        $validated = $request->validate([
            'confirmed_values' => 'required|array|min:1',
            'confirmed_values.*.test_name' => 'required|string|max:255',
            'confirmed_values.*.value' => 'required|numeric',
            'confirmed_values.*.unit' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $draft = \App\Models\PdfSubmissionDraft::where('user_id', $user->id)->findOrFail($draftId);
        $cost = config('credits.costs.pdf_interpretation', 3);

        if (!$this->creditService->hasCredits($user, $cost)) {
            return $this->error('Insufficient credits. Please top up.', 402);
        }

        $submission = LabSubmission::create([
            'user_id' => $user->id,
            'submission_type' => 'pdf',
            'credits_used' => $cost,
            'pdf_report_url' => $draft->pdf_path,
            'pdf_text' => $draft->raw_ocr_text,
            'submitted_at' => now(),
        ]);

        foreach ($validated['confirmed_values'] as $item) {
            LabSubmissionValue::create([
                'lab_submission_id' => $submission->id,
                'test_slug' => \Illuminate\Support\Str::slug($item['test_name']),
                'test_name' => $item['test_name'],
                'unit' => $item['unit'] ?? '',
                'value' => $item['value'],
                'flag' => 'pending',
            ]);
        }

        $prompt = "The following lab values were extracted from a PDF report and confirmed by the user:\n\n";
        foreach ($validated['confirmed_values'] as $item) {
            $prompt .= "- {$item['test_name']}: {$item['value']} {$item['unit']}\n";
        }
        $prompt .= "\nProvide a plain-language interpretation for the patient.";

        $interpretation = AiInterpretation::create([
            'lab_submission_id' => $submission->id,
            'prompt_input' => $prompt,
            'guardrail_flags' => [],
            'status' => 'pending',
        ]);

        $interpText = $this->deepSeek->interpretPdf($interpretation, $prompt);
        if (!$interpText) return $this->error('AI interpretation unavailable. Please try again.', 503);

        if (str_contains($interpText, 'NOT_A_LAB_REPORT')) {
            $interpretation->update(['status' => 'failed', 'error_message' => 'The uploaded file does not appear to be a lab report.']);
            return $this->error('This file does not appear to be a lab report. Please upload a document containing lab test results.', 422);
        }

        $this->creditService->debit($user, $cost, 'pdf_interpretation');
        $draft->update([
            'confirmation_status' => 'confirmed',
            'confirmed_by' => $user->id,
            'confirmed_at' => now(),
            'extracted_tests' => $validated['confirmed_values'],
        ]);

        $submission->load(['values', 'interpretation']);
        return $this->success([
            'submission' => $submission,
            'interpretation' => $interpretation->fresh(),
            'has_interpretation' => !is_null($interpText),
        ], 'Values confirmed and interpretation generated', 201);
    }

    public function submitPdf(Request $request)
    {
        $validated = $request->validate([
            'pdf_base64' => 'required|string',
            'pdf_name' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $cost = config('credits.costs.pdf_interpretation', 3);

        if (!$this->creditService->hasCredits($user, $cost)) {
            return $this->error('Insufficient credits. Please top up.', 402);
        }

        $pdfData = base64_decode($validated['pdf_base64']);
        if (!$pdfData) return $this->error('Invalid PDF data. The file could not be decoded.', 422);

        $fileName = ($validated['pdf_name'] ?? 'report') . '_' . time() . '.pdf';
        $path = 'lab-reports/' . $fileName;
        Storage::put($path, $pdfData);
        $fullPath = Storage::path($path);

        $pdfText = '';
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($fullPath);
            $pdfText = $pdf->getText();
        } catch (\Throwable $e) {
            return $this->error('Could not parse this PDF. The file may be corrupted or not a valid PDF.', 422);
        }

        if (empty(trim($pdfText))) {
            return $this->error('No text could be extracted from this PDF. The file may be scanned or image-based. Please try a text-based PDF.', 422);
        }

        $submission = LabSubmission::create([
            'user_id' => $user->id,
            'submission_type' => 'pdf',
            'credits_used' => $cost,
            'pdf_report_url' => $path,
            'pdf_text' => $pdfText,
            'submitted_at' => now(),
        ]);

        $interpretation = AiInterpretation::create([
            'lab_submission_id' => $submission->id,
            'prompt_input' => $pdfText,
            'guardrail_flags' => [],
            'status' => 'pending',
        ]);

        $interpText = $this->deepSeek->interpretPdf($interpretation, $pdfText);
        if ($interpText) {
            // Check if the LLM flagged this as not a lab report
            if (str_contains($interpText, 'NOT_A_LAB_REPORT')) {
                $interpretation->update([
                    'status' => 'failed',
                    'error_message' => 'The uploaded file does not appear to be a lab report. Please upload a valid lab result document.',
                ]);
                return $this->error('This file does not appear to be a lab report. Please upload a document containing lab test results.', 422);
            }
            $this->creditService->debit($user, $cost, 'pdf_interpretation');
        } else {
            return $this->error('The AI service is currently unavailable. Your credits have not been deducted. Please try again later.', 503);
        }

        $submission->load('interpretation');
        return $this->success([
            'submission' => $submission,
            'interpretation' => $interpretation->fresh(),
            'has_interpretation' => !is_null($interpText),
        ], 'PDF report submitted for interpretation', 201);
    }

    private function buildGuardrailFlags(array $flagged): array
    {
        $criticalCount = 0;
        $highCount = 0;
        $lowCount = 0;

        foreach ($flagged as $f) {
            if (str_starts_with($f['flag'], 'critical')) $criticalCount++;
            elseif ($f['flag'] === 'high') $highCount++;
            elseif ($f['flag'] === 'low') $lowCount++;
        }

        return [
            'total_tests' => count($flagged),
            'normal' => count(array_filter($flagged, fn ($f) => $f['flag'] === 'normal')),
            'high' => $highCount,
            'low' => $lowCount,
            'critical' => $criticalCount,
            'disclaimer_shown' => true,
        ];
    }
}