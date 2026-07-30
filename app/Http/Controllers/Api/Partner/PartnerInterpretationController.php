<?php

namespace App\Http\Controllers\Api\Partner;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Api\BaseController;
use App\Models\LabPartnership;
use App\Models\PartnerInterpretation;
use App\Models\ProviderDirectoryEntry;
use App\Services\ReportRenderer;
use Illuminate\Http\Request;

class PartnerInterpretationController extends BaseController
{
    public function __construct(
        private ReportRenderer $renderer,
    ) {}

    // ── Resolve the authenticated partner ────────────────

    private function resolvePartner(Request $request): LabPartnership
    {
        $provider = $request->user();
        if (!($provider instanceof ProviderDirectoryEntry)) {
            abort(403, 'Invalid provider session.');
        }

        $partnership = LabPartnership::where('provider_id', $provider->id)
            ->whereIn('status', ['active', 'pilot'])
            ->first();

        if (!$partnership) {
            abort(403, 'No active partnership found for this provider.');
        }

        return $partnership;
    }

    // ── Dashboard Stats ──────────────────────────────────

    public function stats(Request $request)
    {
        $partner = $this->resolvePartner($request);

        $thisMonth = now()->startOfMonth();
        $total = PartnerInterpretation::where('partnership_id', $partner->id)->count();
        $thisMonthCount = PartnerInterpretation::where('partnership_id', $partner->id)
            ->where('created_at', '>=', $thisMonth)->count();
        $completed = PartnerInterpretation::where('partnership_id', $partner->id)
            ->where('status', 'completed')->where('created_at', '>=', $thisMonth)->count();
        $totalCost = PartnerInterpretation::where('partnership_id', $partner->id)
            ->where('created_at', '>=', $thisMonth)->sum('cost_to_partner');

        $recent = PartnerInterpretation::where('partnership_id', $partner->id)
            ->latest()->take(10)->get()->map(fn($i) => [
                'id' => $i->id,
                'patient_identifier' => $i->patient_identifier,
                'test_name' => $i->test_name,
                'status' => $i->status,
                'delivery_method' => $i->delivery_method,
                'delivery_status' => $i->delivery_status,
                'created_at' => $i->created_at->toISOString(),
            ]);

        return $this->success([
            'stats' => [
                'total_interpretations' => $total,
                'this_month' => $thisMonthCount,
                'completed' => $completed,
                'total_cost_naira' => round($totalCost / 100, 2),
                'estimated_bill' => $partner->estimatedMonthlyBill(),
                'monthly_allowance' => $partner->monthly_allowance,
                'rate_per_report' => $partner->rateNaira(),
            ],
            'recent' => $recent,
        ]);
    }

    // ── Single Interpretation ─────────────────────────────

    public function store(Request $request)
    {
        $partner = $this->resolvePartner($request);

        $validated = $request->validate([
            'patient_identifier' => 'nullable|string|max:100',
            'test_name' => 'required|string|max:255',
            'value' => 'required|string|max:50',
            'unit' => 'nullable|string|max:50',
            'reference_range_low' => 'nullable|string|max:50',
            'reference_range_high' => 'nullable|string|max:50',
            'sex' => 'nullable|string|max:10',
            'age' => 'nullable|string|max:10',
            'delivery_method' => 'nullable|in:email,whatsapp,sms,pdf',
        ]);

        $interpretation = PartnerInterpretation::create([
            'partnership_id' => $partner->id,
            'status' => 'pending',
            'cost_to_partner' => $partner->rate_per_report ?? 0,
            ...$validated,
        ]);

        // Generate interpretation using the existing DeepSeek AI
        $interpretationText = $this->generateInterpretation($interpretation);
        $interpretation->update([
            'interpretation_text' => $interpretationText,
            'status' => 'completed',
        ]);

        return $this->success([
            'interpretation' => [
                'id' => $interpretation->id,
                'test_name' => $interpretation->test_name,
                'value' => $interpretation->value,
                'unit' => $interpretation->unit,
                'interpretation_text' => $interpretationText,
                'status' => $interpretation->status,
            ],
        ], 'Interpretation completed', 201);
    }

    // ── Bulk CSV Upload ───────────────────────────────────

    public function bulkStore(Request $request)
    {
        $partner = $this->resolvePartner($request);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'delivery_method' => 'nullable|in:email,whatsapp,sms,pdf',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getPathname(), 'r');

        // Detect header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            return $this->error('CSV file is empty or invalid.', 422);
        }

        $batchId = 'batch_' . now()->timestamp . '_' . $partner->id;
        $created = [];
        $rowCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) continue; // skip empty rows

            $rowCount++;
            $data = array_combine(array_pad($headers, count($row), ''), array_pad($row, count($headers), ''));

            if (empty($data['test_name']) || empty($data['value'])) continue;

            $interpretation = PartnerInterpretation::create([
                'partnership_id' => $partner->id,
                'patient_identifier' => $data['patient_id'] ?? $data['patient_identifier'] ?? $data['barcode'] ?? null,
                'test_name' => $data['test_name'],
                'value' => $data['value'],
                'unit' => $data['unit'] ?? null,
                'reference_range_low' => $data['reference_range_low'] ?? $data['ref_low'] ?? null,
                'reference_range_high' => $data['reference_range_high'] ?? $data['ref_high'] ?? null,
                'sex' => $data['sex'] ?? null,
                'age' => $data['age'] ?? null,
                'status' => 'pending',
                'cost_to_partner' => $partner->rate_per_report ?? 0,
                'external_id' => $batchId,
                'delivery_method' => $request->delivery_method,
            ]);

            $interpretationText = $this->generateInterpretation($interpretation);
            $interpretation->update([
                'interpretation_text' => $interpretationText,
                'status' => 'completed',
            ]);

            $created[] = [
                'id' => $interpretation->id,
                'patient_identifier' => $interpretation->patient_identifier,
                'test_name' => $interpretation->test_name,
                'status' => $interpretation->status,
            ];
        }

        fclose($handle);

        return $this->success([
            'batch_id' => $batchId,
            'count' => count($created),
            'items' => $created,
        ], "{$rowCount} interpretations processed", 201);
    }

    // ── Download PDF Report ───────────────────────────────

    public function downloadPdf(Request $request, $id)
    {
        $partner = $this->resolvePartner($request);
        $interpretation = PartnerInterpretation::where('partnership_id', $partner->id)->findOrFail($id);

        $pdf = $this->renderer->renderSingle($interpretation);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="interpretation-' . $id . '.pdf"',
        ]);
    }

    // ── Download Batch PDF ────────────────────────────────

    public function downloadBatchPdf(Request $request)
    {
        $partner = $this->resolvePartner($request);

        $request->validate(['batch_id' => 'required|string']);

        $interpretations = PartnerInterpretation::where('partnership_id', $partner->id)
            ->where('external_id', $request->batch_id)
            ->where('status', 'completed')
            ->get();

        if ($interpretations->isEmpty()) {
            return $this->error('No completed interpretations found for this batch.', 404);
        }

        $pdf = $this->renderer->renderBatch($interpretations->all(), $partner);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="batch-' . $request->batch_id . '.pdf"',
        ]);
    }

    // ── Patient History ───────────────────────────────────

    public function patients(Request $request)
    {
        $partner = $this->resolvePartner($request);
        $page = $request->get('page', 1);

        $patients = PartnerInterpretation::where('partnership_id', $partner->id)
            ->whereNotNull('patient_identifier')
            ->selectRaw('patient_identifier, count(*) as total_tests, MAX(created_at) as last_test')
            ->groupBy('patient_identifier')
            ->orderByDesc('last_test')
            ->paginate(20, ['*'], 'page', $page);

        $patients->getCollection()->transform(function ($p) use ($partner) {
            $latestTests = PartnerInterpretation::where('partnership_id', $partner->id)
                ->where('patient_identifier', $p->patient_identifier)
                ->latest()->take(5)->get()->map(fn($t) => [
                    'id' => $t->id,
                    'test_name' => $t->test_name,
                    'value' => $t->value,
                    'unit' => $t->unit,
                    'status' => $t->status,
                    'created_at' => $t->created_at->toISOString(),
                ]);

            return [
                'patient_identifier' => $p->patient_identifier,
                'total_tests' => $p->total_tests,
                'last_test' => $p->last_test,
                'recent_tests' => $latestTests,
            ];
        });

        return $this->paginated($patients, 'Patients retrieved');
    }

    // ── Batch Status Polling ──────────────────────────────

    public function batchStatus(Request $request, string $batchId)
    {
        $partner = $this->resolvePartner($request);

        $interpretations = PartnerInterpretation::where('partnership_id', $partner->id)
            ->where('external_id', $batchId)
            ->get();

        if ($interpretations->isEmpty()) {
            return $this->error('Batch not found.', 404);
        }

        $pending = $interpretations->where('status', 'pending')->count();
        $completed = $interpretations->where('status', 'completed')->count();
        $failed = $interpretations->where('status', 'failed')->count();

        $items = $interpretations->map(fn($i) => [
            'id' => $i->id,
            'patient_identifier' => $i->patient_identifier,
            'test_name' => $i->test_name,
            'value' => $i->value,
            'unit' => $i->unit,
            'status' => $i->status,
            'delivery_method' => $i->delivery_method,
            'delivery_status' => $i->delivery_status,
            'created_at' => $i->created_at->toISOString(),
        ]);

        return $this->success([
            'batch_id' => $batchId,
            'total' => $interpretations->count(),
            'pending' => $pending,
            'completed' => $completed,
            'failed' => $failed,
            'items' => $items,
            'pdf_url' => $completed > 0 ? '/api/partner/interpretations/batch/pdf' : null,
        ]);
    }

    // ── Deliver All Reports for a Batch ───────────────────

    public function deliverAll(Request $request, string $batchId)
    {
        $partner = $this->resolvePartner($request);

        $request->validate([
            'delivery_method' => 'required|in:email,whatsapp,sms',
            'recipient' => 'required|string|max:255',
        ]);

        $interpretations = PartnerInterpretation::where('partnership_id', $partner->id)
            ->where('external_id', $batchId)
            ->where('status', 'completed')
            ->get();

        if ($interpretations->isEmpty()) {
            return $this->error('No completed interpretations found for this batch.', 404);
        }

        // Generate batch PDF
        $pdf = $this->renderer->renderBatch($interpretations->all(), $partner);

        $method = $request->delivery_method;
        $recipient = $request->recipient;
        $delivered = 0;

        try {
            match ($method) {
                'email' => $this->sendBatchViaEmail($pdf, $recipient, $batchId, $partner),
                'whatsapp' => $this->sendBatchViaWhatsApp($interpretations, $recipient, $batchId, $partner),
                'sms' => $this->sendBatchViaSms($interpretations, $recipient),
                default => throw new \InvalidArgumentException('Unsupported delivery method'),
            };

            // Mark all as delivered
            foreach ($interpretations as $i) {
                $i->update([
                    'delivery_method' => $method,
                    'delivery_status' => 'sent',
                ]);
                $delivered++;
            }

            return $this->success([
                'batch_id' => $batchId,
                'delivered_count' => $delivered,
                'delivery_method' => $method,
            ], "Batch delivered via {$method}");
        } catch (\Throwable $e) {
            return $this->error('Batch delivery failed: ' . $e->getMessage(), 500);
        }
    }

    // ── HL7 Parser Endpoint ───────────────────────────────

    public function hl7Parse(Request $request)
    {
        $partner = $this->resolvePartner($request);

        $request->validate([
            'hl7_message' => 'required|string|max:65535',
            'patient_identifier' => 'nullable|string|max:100',
        ]);

        $parser = new \App\Services\Hl7Parser();
        $result = $parser->parseAndStore(
            $request->hl7_message,
            $partner,
            $request->patient_identifier
        );

        // Auto-generate interpretations for each parsed result
        foreach (PartnerInterpretation::where('external_id', $result['batch_id'])->get() as $i) {
            $text = $this->generateInterpretation($i);
            $i->update(['interpretation_text' => $text, 'status' => 'completed']);
        }

        return response()->json(['ok' => true, 'message' => 'HL7 message processed', 'data' => $result], 201);
    }

    // ── Population Analytics ──────────────────────────────

    public function populationAnalytics(Request $request)
    {
        $partner = $this->resolvePartner($request);
        $days = min((int) $request->get('days', 90), 365);

        $since = now()->subDays($days);

        $interpretations = PartnerInterpretation::where('partnership_id', $partner->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', $since)
            ->get();

        if ($interpretations->isEmpty()) {
            return $this->success([
                'total_patients' => 0,
                'total_tests' => 0,
                'test_distribution' => [],
                'abnormality_rates' => [],
                'volume_by_week' => [],
            ], 'No data available for this period');
        }

        $totalTests = $interpretations->count();
        $totalPatients = $interpretations->unique('patient_identifier')->count();

        // Test distribution
        $testDist = $interpretations->groupBy('test_name')
            ->map(fn($group) => [
                'test_name' => $group->first()->test_name,
                'count' => $group->count(),
                'percentage' => round(($group->count() / $totalTests) * 100, 1),
            ])
            ->sortByDesc('count')
            ->take(20)
            ->values();

        // Abnormality rates (per test)
        $abnormalityRates = $interpretations
            ->groupBy('test_name')
            ->map(function ($group) {
                $total = $group->count();
                if ($total === 0) return null;

                $high = 0;
                $low = 0;
                foreach ($group as $i) {
                    if (!$i->reference_range_low || !$i->reference_range_high) continue;
                    $v = (float) $i->value;
                    $l = (float) $i->reference_range_low;
                    $h = (float) $i->reference_range_high;
                    if ($v < $l) $low++;
                    if ($v > $h) $high++;
                }

                return [
                    'test_name' => $group->first()->test_name,
                    'total' => $total,
                    'high_count' => $high,
                    'low_count' => $low,
                    'normal_count' => $total - $high - $low,
                    'abnormality_rate' => round((($high + $low) / max($total, 1)) * 100, 1),
                ];
            })
            ->filter()
            ->sortByDesc('abnormality_rate')
            ->take(15)
            ->values();

        // Volume by week
        $volumeByWeek = $interpretations
            ->groupBy(fn($i) => $i->created_at->format('Y-\WW'))
            ->map(fn($group) => [
                'week' => $group->first()->created_at->format('Y-\WW'),
                'count' => $group->count(),
                'unique_patients' => $group->unique('patient_identifier')->count(),
            ])
            ->sortBy('week')
            ->values();

        return $this->success([
            'period_days' => $days,
            'total_patients' => $totalPatients,
            'total_tests' => $totalTests,
            'avg_tests_per_patient' => $totalPatients > 0 ? round($totalTests / $totalPatients, 1) : 0,
            'test_distribution' => $testDist,
            'abnormality_rates' => $abnormalityRates,
            'volume_by_week' => $volumeByWeek,
        ]);
    }

    // ── REST API for LIS Integration ──────────────────────

    public function apiInterpretation(Request $request)
    {
        $partner = $this->resolvePartner($request);

        // Accept either a single interpretation or an array
        if ($request->has('interpretations')) {
            // Bulk array payload
            $request->validate([
                'interpretations' => 'required|array|min:1|max:500',
                'interpretations.*.patient_identifier' => 'nullable|string|max:100',
                'interpretations.*.test_name' => 'required|string|max:255',
                'interpretations.*.value' => 'required|string|max:50',
                'interpretations.*.unit' => 'nullable|string|max:50',
                'interpretations.*.reference_range_low' => 'nullable|string|max:50',
                'interpretations.*.reference_range_high' => 'nullable|string|max:50',
                'interpretations.*.sex' => 'nullable|string|max:10',
                'interpretations.*.age' => 'nullable|string|max:10',
            ]);

            $items = $request->interpretations;
        } else {
            // Single interpretation
            $request->validate([
                'patient_identifier' => 'nullable|string|max:100',
                'test_name' => 'required|string|max:255',
                'value' => 'required|string|max:50',
                'unit' => 'nullable|string|max:50',
                'reference_range_low' => 'nullable|string|max:50',
                'reference_range_high' => 'nullable|string|max:50',
                'sex' => 'nullable|string|max:10',
                'age' => 'nullable|string|max:10',
            ]);

            $items = [$request->only([
                'patient_identifier', 'test_name', 'value', 'unit',
                'reference_range_low', 'reference_range_high', 'sex', 'age',
            ])];
        }

        $batchId = 'api_' . now()->timestamp . '_' . $partner->id;
        $results = [];

        foreach ($items as $item) {
            $interpretation = PartnerInterpretation::create([
                'partnership_id' => $partner->id,
                'patient_identifier' => $item['patient_identifier'] ?? null,
                'test_name' => $item['test_name'],
                'value' => $item['value'],
                'unit' => $item['unit'] ?? null,
                'reference_range_low' => $item['reference_range_low'] ?? null,
                'reference_range_high' => $item['reference_range_high'] ?? null,
                'sex' => $item['sex'] ?? null,
                'age' => $item['age'] ?? null,
                'status' => 'pending',
                'cost_to_partner' => $partner->rate_per_report ?? 0,
                'external_id' => $batchId,
            ]);

            $text = $this->generateInterpretation($interpretation);
            $interpretation->update([
                'interpretation_text' => $text,
                'status' => 'completed',
            ]);

            $results[] = [
                'id' => $interpretation->id,
                'test_name' => $interpretation->test_name,
                'value' => $interpretation->value,
                'unit' => $interpretation->unit,
                'interpretation' => $text,
                'status' => $interpretation->status,
            ];
        }

        $responseCode = count($results) === 1 ? 201 : 200;

        return $this->success([
            'batch_id' => $batchId,
            'count' => count($results),
            'results' => $results,
            'webhook_callback_url' => url("/api/partner/interpretations/batch/{$batchId}/status"),
            'pdf_url' => url("/api/partner/interpretations/{$results[0]['id']}/pdf"),
        ], 'Interpretations processed', $responseCode);
    }

    // ── Deliver Report ────────────────────────────────────

    public function deliver(Request $request, $id)
    {
        $partner = $this->resolvePartner($request);

        $request->validate([
            'delivery_method' => 'required|in:email,whatsapp,sms,pdf',
            'recipient' => 'required_if:delivery_method,email,whatsapp,sms|string|max:255',
        ]);

        $interpretation = PartnerInterpretation::where('partnership_id', $partner->id)->findOrFail($id);

        $method = $request->delivery_method;
        $recipient = $request->recipient;

        try {
            match ($method) {
                'email' => $this->sendViaEmail($interpretation, $recipient),
                'whatsapp' => $this->sendViaWhatsApp($interpretation, $recipient, $partner),
                'sms' => $this->sendViaSms($interpretation, $recipient),
                'pdf' => null, // PDF is downloaded, not sent
                default => throw new \InvalidArgumentException('Unsupported delivery method'),
            };

            $interpretation->update([
                'delivery_method' => $method,
                'delivery_status' => 'sent',
            ]);

            return $this->success(null, "Report delivered via {$method}");
        } catch (\Throwable $e) {
            $interpretation->update([
                'delivery_method' => $method,
                'delivery_status' => 'failed',
            ]);

            return $this->error('Delivery failed: ' . $e->getMessage(), 500);
        }
    }

    // ── Private Helpers ───────────────────────────────────

    private function generateInterpretation(PartnerInterpretation $i): string
    {
        try {
            $service = app(\App\Services\DeepSeekService::class);

            $rangeInfo = '';
            if ($i->reference_range_low && $i->reference_range_high) {
                $rangeInfo = "The normal reference range is {$i->reference_range_low} to {$i->reference_range_high} {$i->unit}.";
            }

            $prompt = "You are a medical lab result interpreter. Explain this test result in plain, simple language a patient can understand. Keep it under 3 sentences.\n\n"
                . "Test: {$i->test_name}\n"
                . "Result: {$i->value} {$i->unit}\n"
                . ($i->sex ? "Patient sex: {$i->sex}\n" : '')
                . ($i->age ? "Patient age: {$i->age}\n" : '')
                . "{$rangeInfo}\n\n"
                . "Important: Add 'This is not a medical diagnosis.' at the end.";

            $response = $service->ask($prompt, maxTokens: 200, temperature: 0.3);
            return trim($response ?? 'No interpretation available.');
        } catch (\Throwable) {
            return (new ReportRenderer)->generateFallbackText($i);
        }
    }

    private function sendViaEmail(PartnerInterpretation $i, string $recipient): void
    {
        $pdf = $this->renderer->renderSingle($i);

        \Mail::send([], [], function ($message) use ($recipient, $pdf, $i) {
            $message->to($recipient)
                ->subject('Your Lab Result Interpretation: ' . $i->test_name)
                ->html('<p>Please find attached your lab result interpretation.</p>')
                ->attachData($pdf, 'interpretation.pdf', ['mime' => 'application/pdf']);
        });
    }

    private function sendViaWhatsApp(PartnerInterpretation $i, string $recipient, LabPartnership $partner): void
    {
        $text = "*{$i->test_name} Result*\n\n"
            . "Value: {$i->value} {$i->unit}\n\n"
            . ($i->interpretation_text ? wordwrap($i->interpretation_text, 60) . "\n\n" : '')
            . "Sent by {$partner->provider->name}\n"
            . "_This is not a medical diagnosis._";

        $termii = app(\App\Services\TermiiService::class);
        if (method_exists($termii, 'sendWhatsApp')) {
            $termii->sendWhatsApp($recipient, $text);
        } else {
            $termii->sendSms($recipient, strip_tags($text));
        }
    }

    private function sendViaSms(PartnerInterpretation $i, string $recipient): void
    {
        $text = "{$i->test_name}: {$i->value} {$i->unit}. "
            . ($i->interpretation_text
                ? substr(strip_tags($i->interpretation_text), 0, 300)
                : 'Result ready. Contact your provider for details.');

        $termii = app(\App\Services\TermiiService::class);
        $termii->sendSms($recipient, $text);
    }

    private function sendBatchViaEmail(string $pdf, string $recipient, string $batchId, LabPartnership $partner): void
    {
        \Mail::send([], [], function ($message) use ($recipient, $pdf, $partner) {
            $message->to($recipient)
                ->subject('Batch Lab Results from ' . $partner->provider->name)
                ->html('<p>Please find attached your batch lab results interpretation from ' . e($partner->provider->name) . '.</p>')
                ->attachData($pdf, 'batch-results.pdf', ['mime' => 'application/pdf']);
        });
    }

    private function sendBatchViaWhatsApp($interpretations, string $recipient, string $batchId, LabPartnership $partner): void
    {
        $tests = $interpretations->take(5)->map(fn($i) => "• {$i->test_name}: {$i->value} {$i->unit}")->join("\n");
        $remaining = $interpretations->count() - 5;

        $text = "*Batch Results from {$partner->provider->name}*\n\n"
            . "{$interpretations->count()} tests processed:\n"
            . $tests
            . ($remaining > 0 ? "\n+ {$remaining} more tests" : '')
            . "\n\nDownload full report from your partner portal.\n"
            . "_This is not a medical diagnosis._";

        $termii = app(\App\Services\TermiiService::class);
        if (method_exists($termii, 'sendWhatsApp')) {
            $termii->sendWhatsApp($recipient, $text);
        } else {
            $termii->sendSms($recipient, strip_tags($text));
        }
    }

    private function sendBatchViaSms($interpretations, string $recipient): void
    {
        $first = $interpretations->first();
        $text = "{$interpretations->count()} lab results processed. "
            . ($first ? "First: {$first->test_name} = {$first->value} {$first->unit}. " : '')
            . "Check your email or partner portal for the full report.";

        $termii = app(\App\Services\TermiiService::class);
        $termii->sendSms($recipient, $text);
    }
}