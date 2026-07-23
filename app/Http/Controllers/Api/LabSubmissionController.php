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
    ) {}

    /**
     * List available test panels.
     */
    public function panels()
    {
        $panels = TestPanel::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->success(['panels' => $panels]);
    }

    /**
     * Show a single panel with its tests and reference range metadata.
     */
    public function panelShow(string $slug)
    {
        $panel = TestPanel::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return $this->success(['panel' => $panel->load('ranges')]);
    }

    /**
     * Get the user's submission history.
     */
    public function index(Request $request)
    {
        $submissions = $request->user()
            ->labSubmissions()
            ->with(['testPanel:id,name', 'values', 'interpretation'])
            ->latest('submitted_at')
            ->paginate(20);

        return $this->paginated($submissions);
    }

    /**
     * Get a single submission with full interpretation.
     */
    public function show(int $id, Request $request)
    {
        $submission = $request->user()
            ->labSubmissions()
            ->with(['testPanel', 'values', 'interpretation'])
            ->findOrFail($id);

        return $this->success(['submission' => $submission]);
    }

    /**
     * Submit lab values: flag → interpret → store.
     */
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

        // Check credits
        if (!$this->creditService->hasCredits($user, $cost)) {
            return $this->error('Insufficient credits. Please top up.', 402);
        }

        // Deduct credits
        $this->creditService->debit($user, $cost, 'lab_interpretation');

        // Create submission
        $submission = LabSubmission::create([
            'user_id' => $user->id,
            'test_panel_id' => $panel->id,
            'credits_used' => $cost,
            'submitted_at' => now(),
        ]);

        // Validate, flag, and store each value
        $flaggedValues = [];

        foreach ($validated['values'] as $input) {
            $range = TestReferenceRange::where('test_slug', $input['test_slug'])
                ->where('test_panel_id', $panel->id)
                ->first();

            if (!$range) {
                // Continue gracefully — no reference data for this test
                continue;
            }

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

        // Build prompt and create AI interpretation record
        $prompt = $this->promptBuilder->build($submission, $flaggedValues);

        $interpretation = AiInterpretation::create([
            'lab_submission_id' => $submission->id,
            'prompt_input' => $prompt,
            'guardrail_flags' => $this->buildGuardrailFlags($flaggedValues),
            'status' => 'pending',
        ]);

        // Call DeepSeek (graceful degradation)
        $interpText = $this->deepSeek->interpret($interpretation, $flaggedValues);

        $submission->load(['values', 'interpretation']);

        return $this->success([
            'submission' => $submission,
            'flagged_values' => $flaggedValues,
            'has_interpretation' => !is_null($interpText),
        ], 'Lab results submitted', 201);
    }

    /**
     * Trend data: historical values for a given test slug across all user submissions.
     */
    public function trends(Request $request)
    {
        $validated = $request->validate([
            'test_slug' => 'required|string',
        ]);

        $values = LabSubmissionValue::where('test_slug', $validated['test_slug'])
            ->whereHas('submission', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('submission:id,user_id,test_panel_id,submitted_at')
            ->latest('created_at')
            ->get()
            ->map(fn ($v) => [
                'value' => $v->value,
                'flag' => $v->flag,
                'unit' => $v->unit,
                'date' => $v->submission->submitted_at?->toDateString() ?? $v->created_at->toDateString(),
            ]);

        return $this->success(['trends' => $values]);
    }

    /**
     * Submit a PDF lab report for AI interpretation.
     */
    public function submitPdf(Request $request)
    {
        $validated = $request->validate([
            'pdf_base64' => 'required|string',
            'pdf_name' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $cost = config('credits.costs.pdf_interpretation', 3);

        // Check credits
        if (!$this->creditService->hasCredits($user, $cost)) {
            return $this->error('Insufficient credits. Please top up.', 402);
        }

        // Decode base64 and save to temp file
        $pdfData = base64_decode($validated['pdf_base64']);
        if (!$pdfData) {
            return $this->error('Invalid PDF data. The file could not be decoded.', 422);
        }

        $fileName = ($validated['pdf_name'] ?? 'report') . '_' . time() . '.pdf';
        $path = 'lab-reports/' . $fileName;
        Storage::put($path, $pdfData);
        $fullPath = Storage::path($path);

        // Extract text from PDF using smalot/pdfparser (pure PHP, no binary needed)
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

        // Create submission
        $submission = LabSubmission::create([
            'user_id' => $user->id,
            'submission_type' => 'pdf',
            'credits_used' => $cost,
            'pdf_report_url' => $path,
            'pdf_text' => $pdfText,
            'submitted_at' => now(),
        ]);

        // Create AI interpretation
        $interpretation = AiInterpretation::create([
            'lab_submission_id' => $submission->id,
            'prompt_input' => $pdfText,
            'guardrail_flags' => [],
            'status' => 'pending',
        ]);

        // Call DeepSeek
        $interpText = $this->deepSeek->interpretPdf($interpretation, $pdfText);

        // Only deduct credits if interpretation succeeded
        if ($interpText) {
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

    /**
     * Build guardrail metadata for the interpretation.
     */
    private function buildGuardrailFlags(array $flagged): array
    {
        $criticalCount = 0;
        $highCount = 0;
        $lowCount = 0;

        foreach ($flagged as $f) {
            if (str_starts_with($f['flag'], 'critical')) {
                $criticalCount++;
            } elseif ($f['flag'] === 'high') {
                $highCount++;
            } elseif ($f['flag'] === 'low') {
                $lowCount++;
            }
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
