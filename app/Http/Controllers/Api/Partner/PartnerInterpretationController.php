<?php

namespace App\Http\Controllers\Api\Partner;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Api\BaseController;
use App\Models\InterpretationOverride;
use App\Models\LabPartnership;
use App\Models\PartnerInterpretation;
use App\Models\ProviderDirectoryEntry;
use App\Services\ReferenceRangeService;
use App\Services\ReportRenderer;
use Illuminate\Http\Request;

class PartnerInterpretationController extends BaseController
{
    public function __construct(
        private ReportRenderer $renderer,
        private ReferenceRangeService $referenceRangeService,
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

    // ── ROI Metrics ────────────────────────────────────

    public function roi(Request $request)
    {
        $partner = $this->resolvePartner($request);
        $days = min((int) $request->get('days', 90), 365);

        $metrics = app(\App\Services\RoiMetricsService::class)->calculate($partner, $days);

        return $this->success(['roi' => $metrics]);
    }

    // ── Delivery Health ─────────────────────────────────

    public function deliveryHealth(Request $request)
    {
        $partner = $this->resolvePartner($request);

        $thisMonth = now()->startOfMonth();
        $attempts = \App\Models\DeliveryAttempt::whereHas(
            'interpretation',
            fn($q) => $q->where('partnership_id', $partner->id)
        )
        ->where('created_at', '>=', $thisMonth)
        ->get();

        $sent = $attempts->where('status', 'sent')->count();
        $failed = $attempts->where('status', 'failed')->count();
        $pendingRetry = $attempts->where('status', 'failed')
            ->where('next_retry_at', '!=', null)
            ->where('next_retry_at', '<=', now())
            ->count();
        $total = $attempts->count();

        $deliveryRate = $total > 0 ? round(($sent / $total) * 100, 1) : 0;

        $recentFailed = $attempts->where('status', 'failed')
            ->sortByDesc('created_at')
            ->take(5)
            ->map(fn($a) => [
                'interpretation_id' => $a->interpretation_id,
                'method' => $a->delivery_method,
                'error' => $a->error_message,
                'attempt' => $a->attempt_number,
                'next_retry' => $a->next_retry_at?->toISOString(),
            ])
            ->values();

        return $this->success([
            'delivery_health' => [
                'this_month' => [
                    'total_attempts' => $total,
                    'sent' => $sent,
                    'failed' => $failed,
                    'pending_retry' => $pendingRetry,
                    'delivery_rate' => $deliveryRate . '%',
                ],
                'recent_failures' => $recentFailed,
            ],
        ]);
    }

    // ── Panels ──────────────────────────────────────────

    public function panels(Request $request)
    {
        $panels = \App\Models\InterpretationPanel::where('status', 'approved')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'description' => $p->description,
                'test_codes' => $p->test_codes,
                'layout_sections' => $p->layout_sections,
                'test_count' => count($p->test_codes),
                'version' => $p->version,
            ]);

        return $this->success(['panels' => $panels]);
    }

    // ── View / Edit Interpretation (dual-view) ──────────────

    public function show(Request $request, $id)
    {
        $partner = $this->resolvePartner($request);
        $i = PartnerInterpretation::where('partnership_id', $partner->id)->findOrFail($id);

        return $this->success([
            'id' => $i->id,
            'patient_identifier' => $i->patient_identifier,
            'test_name' => $i->test_name,
            'value' => $i->value,
            'unit' => $i->unit,
            'reference_range_low' => $i->reference_range_low,
            'reference_range_high' => $i->reference_range_high,
            'sex' => $i->sex,
            'age' => $i->age,
            'interpretation_text' => $i->interpretation_text,
            'clinician_interpretation_text' => $i->clinician_interpretation_text,
            'version_for_patient' => $i->version_for_patient,
            'status' => $i->status,
            'delivery_method' => $i->delivery_method,
            'delivery_status' => $i->delivery_status,
            'created_at' => $i->created_at->toISOString(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $partner = $this->resolvePartner($request);
        $i = PartnerInterpretation::where('partnership_id', $partner->id)->findOrFail($id);
        $provider = $request->user();

        $validated = $request->validate([
            'interpretation_text' => 'nullable|string|max:5000',
            'clinician_interpretation_text' => 'nullable|string|max:5000',
            'version_for_patient' => 'nullable|boolean',
            'reference_range_low' => 'nullable|string|max:50',
            'reference_range_high' => 'nullable|string|max:50',
            'override_reason' => 'nullable|string|max:1000', // why the change is being made
        ]);

        // ── Snapshot original state BEFORE update ──
        $originalPatientText = $i->interpretation_text;
        $originalClinicianText = $i->clinician_interpretation_text;
        $originalStatus = $i->status;
        $originalVersion = $i->version_for_patient;

        $i->update($validated);

        // ── Determine which fields actually changed ──
        $changedFields = [];
        if (isset($validated['interpretation_text']) && $validated['interpretation_text'] !== $originalPatientText) {
            $changedFields[] = 'patient_text';
        }
        if (isset($validated['clinician_interpretation_text']) && $validated['clinician_interpretation_text'] !== $originalClinicianText) {
            $changedFields[] = 'clinician_text';
        }
        if (isset($validated['version_for_patient']) && $validated['version_for_patient'] !== $originalVersion) {
            $changedFields[] = 'version_for_patient';
        }
        if (isset($validated['reference_range_low']) || isset($validated['reference_range_high'])) {
            $changedFields[] = 'reference_range';
        }

        // ── Create immutable audit log entry ──
        if (!empty($changedFields)) {
            InterpretationOverride::create([
                'interpretation_id' => $i->id,
                'overridden_by' => $provider instanceof ProviderDirectoryEntry ? $provider->id : null,
                'original_clinician_text' => $originalClinicianText,
                'original_patient_text' => $originalPatientText,
                'original_status' => $originalStatus,
                'new_clinician_text' => $i->clinician_interpretation_text,
                'new_patient_text' => $i->interpretation_text,
                'new_status' => $i->status,
                'override_type' => 'edit',
                'override_reason' => $validated['override_reason'] ?? 'Manual edit by partner',
                'changed_fields' => implode(',', $changedFields),
            ]);
        }

        return $this->success([
            'interpretation' => [
                'id' => $i->id,
                'interpretation_text' => $i->interpretation_text,
                'clinician_interpretation_text' => $i->clinician_interpretation_text,
                'version_for_patient' => $i->version_for_patient,
            ],
        ], 'Interpretation updated');
    }

    /**
     * Suppress an interpretation — soft-delete from patient view, retains for audit.
     */
    public function suppress(Request $request, $id)
    {
        $partner = $this->resolvePartner($request);
        $i = PartnerInterpretation::where('partnership_id', $partner->id)->findOrFail($id);
        $provider = $request->user();

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $originalStatus = $i->status;

        // Mark as suppressed — stays in DB, excluded from patient views
        $i->update(['status' => 'suppressed']);

        InterpretationOverride::create([
            'interpretation_id' => $i->id,
            'overridden_by' => $provider instanceof ProviderDirectoryEntry ? $provider->id : null,
            'original_clinician_text' => $i->clinician_interpretation_text,
            'original_patient_text' => $i->interpretation_text,
            'original_status' => $originalStatus,
            'new_clinician_text' => $i->clinician_interpretation_text,
            'new_patient_text' => $i->interpretation_text,
            'new_status' => 'suppressed',
            'override_type' => 'suppress',
            'override_reason' => $validated['reason'],
            'changed_fields' => 'status',
        ]);

        return $this->success(null, 'Interpretation suppressed. It will no longer appear in patient-facing views.');
    }

    /**
     * Get full audit history for a single interpretation.
     */
    public function history(Request $request, $id)
    {
        $partner = $this->resolvePartner($request);
        $i = PartnerInterpretation::where('partnership_id', $partner->id)->findOrFail($id);

        $overrides = InterpretationOverride::where('interpretation_id', $i->id)
            ->with('overriddenBy:id,name,phone,email')
            ->latest()
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'override_type' => $o->override_type,
                'override_reason' => $o->override_reason,
                'changed_fields' => $o->changed_fields ? explode(',', $o->changed_fields) : [],
                'original' => [
                    'patient_text' => $o->original_patient_text,
                    'clinician_text' => $o->original_clinician_text,
                    'status' => $o->original_status,
                ],
                'new' => [
                    'patient_text' => $o->new_patient_text,
                    'clinician_text' => $o->new_clinician_text,
                    'status' => $o->new_status,
                ],
                'overridden_by' => $o->overriddenBy?->name ?? 'System',
                'overridden_at' => $o->created_at->toISOString(),
            ]);

        return $this->success([
            'interpretation_id' => $i->id,
            'current_status' => $i->status,
            'total_overrides' => $overrides->count(),
            'overrides' => $overrides,
        ]);
    }

    public function toggleVersion(Request $request, $id)
    {
        $partner = $this->resolvePartner($request);
        $i = PartnerInterpretation::where('partnership_id', $partner->id)->findOrFail($id);

        $i->update([
            'version_for_patient' => !$i->version_for_patient,
        ]);

        return $this->success([
            'version_for_patient' => $i->version_for_patient,
            'active_text' => $i->version_for_patient
                ? $i->interpretation_text
                : $i->clinician_interpretation_text,
        ], $i->version_for_patient
            ? 'Switched to patient version'
            : 'Switched to clinician version');
    }

    // ── Download PDF Report ───────────────────────────────

    public function downloadPdf(Request $request, $id)
    {
        $partner = $this->resolvePartner($request);
        $interpretation = PartnerInterpretation::where('partnership_id', $partner->id)->findOrFail($id);

        $version = $request->query('version', 'patient');
        $pdf = $this->renderer->renderSingle($interpretation, $version);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="interpretation-' . $id . ($version === 'clinician' ? '-clinician' : '') . '.pdf"',
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

    /**
     * Generate both clinician and patient interpretations in one LLM call.
     * Stores both versions on the model. Returns the patient-facing text
     * for backward compatibility.
     */
    private function generateInterpretation(PartnerInterpretation $i): string
    {
        try {
            // ── STEP 1: Classify the result against verified reference ranges ──
            $parsedAge = null;
            if ($i->age) {
                $parsedAge = (float) preg_replace('/[^0-9.]/', '', $i->age);
            }

            $classification = $this->referenceRangeService->classify(
                testName: $i->test_name,
                value: (float) $i->value,
                unit: $i->unit ?? '',
                sex: $i->sex,
                age: $parsedAge ?: null,
            );

            $classificationStatus = $classification['status'];
            $confidence = $classification['confidence'];

            $rangeLow = $classification['range_low'];
            $rangeHigh = $classification['range_high'];
            $rangeUnit = $classification['unit'];
            $source = $classification['source'] ?? 'standard clinical guidelines';
            $reason = $classification['reason'];

            $statusLabel = match ($classificationStatus) {
                'critical_low' => 'CRITICALLY LOW — urgent medical attention needed',
                'critical_high' => 'CRITICALLY HIGH — urgent medical attention needed',
                'abnormal_low' => 'BELOW normal range',
                'abnormal_high' => 'ABOVE normal range',
                'normal' => 'WITHIN normal range',
                'unknown' => '— reference range not available',
            };

            $escalation = '';
            if (in_array($classificationStatus, ['critical_low', 'critical_high'])) {
                $escalation = "IMPORTANT: This result is critically outside the normal range. The patient should speak to a doctor immediately.";
            } elseif (in_array($classificationStatus, ['abnormal_low', 'abnormal_high'])) {
                $escalation = "IMPORTANT: This result is outside the normal range — the patient should speak to a doctor.";
            }

            $service = app(\App\Services\DeepSeekService::class);

            // ── STEP 2: Single LLM call producing a JSON response with both versions ──
            $prompt = "You are a clinical lab result interpreter for LabDoc, a Nigerian health-tech platform. "
                . "A test result has been CLASSIFIED by a verified reference range database. "
                . "You must respond with TWO versions of the interpretation in a strict JSON format.\n\n"
                . "Test: {$i->test_name}\n"
                . "Result: {$i->value} {$i->unit}\n"
                . "Classification: {$statusLabel}\n"
                . "Verified normal range: {$rangeLow} – {$rangeHigh} {$rangeUnit} (source: {$source})\n"
                . ($i->sex ? "Patient sex: {$i->sex}\n" : '')
                . ($i->age ? "Patient age: {$i->age}\n" : '')
                . "Classification reasoning: {$reason}\n"
                . ($escalation ? "{$escalation}\n" : '')
                . "\nResponse format — output ONLY valid JSON, no other text:\n"
                . "{\n"
                . '  "clinician": "Technical interpretation with medical terminology. Include differential diagnoses to consider, clinical context, relevant guidelines, and actionable recommendations for a healthcare professional. 2-4 sentences.",'
                . "\n"
                . '  "patient": "Plain-language explanation using grade 7-8 English. Explain what the test measures, what the result means in simple terms, and what to do next. Use short sentences. 2-3 sentences. End with \'This is not a medical diagnosis.\'"'
                . "\n}";

            $response = $service->ask($prompt, maxTokens: 350, temperature: 0.3);

            if (!$response) {
                throw new \RuntimeException('LLM returned empty response');
            }

            // ── STEP 3: Parse the JSON response ──
            $json = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // LLM didn't return valid JSON — try to extract
                $extracted = $this->extractJsonFromResponse($response);
                if ($extracted) {
                    $json = $extracted;
                } else {
                    // Fallback: use the raw response as patient text, generate clinician fallback
                    $patientText = trim($response);
                    $clinicianText = $this->generateClinicianFallback($i, $classification);
                    $i->update([
                        'interpretation_text' => $patientText,
                        'clinician_interpretation_text' => $clinicianText,
                        'classification_status' => $classificationStatus,
                        'confidence_score' => $confidence,
                        'escalation_level' => $this->escalationLevelFromStatus($classificationStatus),
                        'escalation_message' => $this->escalationMessageFromLevel($this->escalationLevelFromStatus($classificationStatus), $classificationStatus),
                    ]);
                    return $patientText;
                }
            }

            $patientText = $json['patient'] ?? ($json['patient_interpretation'] ?? 'No patient interpretation available.');
            $clinicianText = $json['clinician'] ?? ($json['clinician_interpretation'] ?? $this->generateClinicianFallback($i, $classification));

            // ── STEP 4: Store both versions + classification metadata ──
            $escalationLevel = match ($classificationStatus) {
                'critical_low', 'critical_high' => 'urgent',
                'abnormal_low', 'abnormal_high' => 'flagged',
                'normal' => 'info',
                default => 'info',
            };

            $escalationMessage = match ($escalationLevel) {
                'urgent' => 'This result is critically outside range — seek urgent medical attention.',
                'flagged' => 'This result is outside the normal range — speak to a doctor.',
                'info' => $classificationStatus === 'normal'
                    ? 'This result is within the normal range.'
                    : '',
            };

            // ── Admin notification for unknown tests (missing reference range) ──
            if ($classificationStatus === 'unknown') {
                $this->notifyAdminMissingRange($i->test_name);
            }

            $i->update([
                'interpretation_text' => $patientText,
                'clinician_interpretation_text' => $clinicianText,
                'classification_status' => $classificationStatus,
                'confidence_score' => $confidence,
                'escalation_level' => $escalationLevel,
                'escalation_message' => $escalationMessage,
            ]);

            return $patientText;
        } catch (\Throwable) {
            $fallback = (new ReportRenderer)->generateFallbackText($i);
            $i->update([
                'interpretation_text' => $fallback,
                'clinician_interpretation_text' => $this->generateClinicianFallback($i, ['status' => 'unknown']),
                'classification_status' => 'unknown',
                'confidence_score' => 0,
                'escalation_level' => 'info',
                'escalation_message' => 'This test could not be classified against verified reference ranges.',
            ]);
            return $fallback;
        }
    }

    /**
     * Try to extract JSON from a malformed LLM response.
     */
    private function extractJsonFromResponse(string $response): ?array
    {
        // Try to find JSON between { and }
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return null;
    }

    /**
     * Generate a clinician-facing fallback when the LLM fails to produce one.
     */
    private function generateClinicianFallback(PartnerInterpretation $i, array $classification): string
    {
        $status = $classification['status'] ?? 'unknown';
        $rangeLow = $classification['range_low'] ?? $i->reference_range_low;
        $rangeHigh = $classification['range_high'] ?? $i->reference_range_high;
        $unit = $classification['unit'] ?? $i->unit ?? '';
        $source = $classification['source'] ?? 'standard reference';
        $confidence = $classification['confidence'] ?? 0;

        $header = "Test: {$i->test_name}\n"
            . "Result: {$i->value} {$unit}\n"
            . "Reference range: {$rangeLow} – {$rangeHigh} {$unit} ({$source})\n"
            . ($i->sex ? "Sex: {$i->sex}  " : '')
            . ($i->age ? "Age: {$i->age}  " : '');

        $body = match ($status) {
            'critical_low' => "CRITICAL: Result is below the critical threshold. Immediate clinical evaluation required. "
                . "Consider severe anaemia, acute blood loss, bone marrow suppression, or haemolysis. "
                . "Correlate with clinical presentation and order urgent repeat testing.",
            'critical_high' => "CRITICAL: Result exceeds the critical threshold. Immediate clinical evaluation required. "
                . "Consider polycythaemia vera, severe dehydration, or cardiopulmonary disease. "
                . "Correlate with clinical presentation and order urgent repeat testing.",
            'abnormal_low' => "ABNORMAL LOW: Result is below the reference range. "
                . "Consider nutritional deficiency, chronic disease, medication effects, or early pathology. "
                . "Clinical correlation advised. Consider repeat testing in 2–4 weeks if asymptomatic.",
            'abnormal_high' => "ABNORMAL HIGH: Result exceeds the reference range. "
                . "Consider inflammatory processes, metabolic disorder, medication effects, or organ dysfunction. "
                . "Clinical correlation recommended. Further targeted testing may be warranted.",
            'normal' => "NORMAL: Result is within the verified reference range for the patient's demographic profile. "
                . "No further action required based on this test alone. Continue routine monitoring as clinically indicated.",
            default => "Reference range not available for this test with the patient's demographic profile. "
                . "Interpret with clinical judgment using institutional or literature-based norms. "
                . "Confidence score: {$confidence}/100.",
        };

        return "{$header}\n\n{$body}\n\n— Auto-generated by LabDoc Reference Range Engine (confidence: {$confidence}%)";
    }

    private function escalationLevelFromStatus(string $status): string
    {
        return match ($status) {
            'critical_low', 'critical_high' => 'urgent',
            'abnormal_low', 'abnormal_high' => 'flagged',
            'normal' => 'info',
            default => 'info',
        };
    }

    private function escalationMessageFromLevel(string $level, string $status): string
    {
        return match ($level) {
            'urgent' => 'This result is critically outside range — seek urgent medical attention.',
            'flagged' => 'This result is outside the normal range — speak to a doctor.',
            default => $status === 'normal'
                ? 'This result is within the normal range.'
                : 'This test could not be classified against verified reference ranges.',
        };
    }

    /**
     * Create an admin notification for a missing reference range.
     */
    private function notifyAdminMissingRange(string $testName): void
    {
        try {
            \App\Models\AdminNotification::create([
                'admin_id' => \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()?->id ?? 1,
                'title' => 'Missing Reference Range',
                'body' => "The test '{$testName}' was submitted but no verified reference range exists in the database. Please add this range at /admin/clinical/ranges.",
                'target' => 'all',
                'sent_at' => now(),
            ]);
        } catch (\Throwable) {
            // Silently fail — don't interrupt interpretation
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