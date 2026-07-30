<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Api\BaseController;
use App\Models\LabPartnership;
use App\Models\PartnerInterpretation;
use App\Models\PartnerInvoice;
use App\Models\ProviderDirectoryEntry;
use Illuminate\Http\Request;

class AdminPartnershipController extends BaseController
{
    // ── List Partnerships ─────────────────────────────

    public function index()
    {
        $partnerships = LabPartnership::with('provider:id,name,slug,type,phone,email')
            ->latest()
            ->paginate(25);

        $partnerships->getCollection()->transform(function ($p) {
            $p->monthly_count = $p->monthlyCount();
            $p->estimated_bill = $p->estimatedMonthlyBill();
            return $p;
        });

        return $this->paginated($partnerships);
    }

    // ── Show Single Partnership ───────────────────────

    public function show($id)
    {
        $partnership = LabPartnership::with('provider')->findOrFail($id);

        $partnership->monthly_count = $partnership->monthlyCount();
        $partnership->estimated_bill = $partnership->estimatedMonthlyBill();

        $recentInterpretations = PartnerInterpretation::where('partnership_id', $id)
            ->latest()->take(20)->get();

        return $this->success([
            'partnership' => $partnership,
            'recent_interpretations' => $recentInterpretations,
        ]);
    }

    // ── Create Partnership ─────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'provider_id' => 'required|exists:provider_directory_entries,id',
            'plan_tier' => 'required|in:pilot,standard,premium',
            'pricing_model' => 'required|in:per_report,volume_tier,flat_monthly',
            'rate_per_report' => 'required|integer|min:0',
            'monthly_allowance' => 'nullable|integer|min:0',
            'overage_rate' => 'nullable|integer|min:0',
            'white_label' => 'boolean',
            'brand_logo_url' => 'nullable|string|max:2048',
            'brand_primary_color' => 'nullable|string|max:7',
            'brand_contact_info' => 'nullable|string|max:500',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date|after:contract_start',
            'ndpa_agreement_signed' => 'boolean',
        ]);

        $partnership = LabPartnership::create($validated + ['status' => 'active']);

        // Upgrade provider to partner status
        ProviderDirectoryEntry::where('id', $validated['provider_id'])
            ->update(['partner_status' => 'affiliate']);

        return $this->success([
            'partnership' => $partnership->load('provider:id,name'),
        ], 'Partnership created', 201);
    }

    // ── Update Partnership ─────────────────────────────

    public function update(Request $request, $id)
    {
        $partnership = LabPartnership::findOrFail($id);

        $validated = $request->validate([
            'plan_tier' => 'sometimes|in:pilot,standard,premium',
            'pricing_model' => 'sometimes|in:per_report,volume_tier,flat_monthly',
            'rate_per_report' => 'sometimes|integer|min:0',
            'monthly_allowance' => 'nullable|integer|min:0',
            'overage_rate' => 'nullable|integer|min:0',
            'white_label' => 'boolean',
            'brand_logo_url' => 'nullable|string|max:2048',
            'brand_primary_color' => 'nullable|string|max:7',
            'brand_contact_info' => 'nullable|string|max:500',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date|after:contract_start',
            'status' => 'sometimes|in:active,pilot,expired,cancelled',
            'ndpa_agreement_signed' => 'boolean',
        ]);

        $partnership->update($validated);

        return $this->success([
            'partnership' => $partnership->fresh()->load('provider:id,name'),
        ], 'Partnership updated');
    }

    // ── Invoices ────────────────────────────────────────

    public function invoices(Request $request)
    {
        $invoices = PartnerInvoice::with('partnership.provider:id,name')
            ->latest()
            ->paginate(25);

        return $this->paginated($invoices);
    }

    public function partnershipInvoices($id)
    {
        $invoices = PartnerInvoice::where('partnership_id', $id)
            ->with('partnership.provider:id,name')
            ->latest()
            ->paginate(25);

        return $this->paginated($invoices);
    }

    public function generateInvoice($id)
    {
        $partnership = LabPartnership::findOrFail($id);
        $billing = app(\App\Services\PartnerBillingService::class);

        // Check if invoice already exists for this month
        $exists = PartnerInvoice::where('partnership_id', $id)
            ->where('period_start', now()->startOfMonth()->toDateString())
            ->exists();

        if ($exists) {
            return $this->error('An invoice already exists for this month.', 409);
        }

        $invoice = $billing->generateInvoice($partnership);

        return $this->success([
            'invoice' => $invoice->load('partnership.provider:id,name'),
        ], 'Invoice generated', 201);
    }

    public function generateAllInvoices()
    {
        $billing = app(\App\Services\PartnerBillingService::class);
        $invoices = $billing->generateAllMonthlyInvoices();

        return $this->success([
            'count' => count($invoices),
            'invoices' => $invoices,
        ], count($invoices) > 0 ? 'Invoices generated' : 'No new invoices needed');
    }

    // ── Delete Partnership ─────────────────────────────

    public function destroy($id)
    {
        $partnership = LabPartnership::findOrFail($id);
        $partnership->delete();

        return $this->success(null, 'Partnership deleted');
    }

    // ── Proposal PDF ─────────────────────────────────────

    public function proposalPdf($id)
    {
        $partnership = LabPartnership::with('provider')->findOrFail($id);
        $provider = $partnership->provider;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.partner-proposal', [
            'partnership' => $partnership,
            'provider' => $provider,
        ]);
        $pdf->setPaper('A4');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="proposal-' . $provider->slug . '.pdf"',
        ]);
    }

    // ── Partner Health Scores ────────────────────────────

    public function healthScores()
    {
        $partnerships = LabPartnership::with('provider:id,name,slug,type')->get();

        $scores = $partnerships->map(function ($p) {
            return [
                'id' => $p->id,
                'provider_name' => $p->provider->name,
                'provider_type' => $p->provider->type,
                'plan_tier' => $p->plan_tier,
                'status' => $p->status,
                'health' => $this->calculateHealthScore($p),
                'billing' => [
                    'current_month_bill' => $p->estimatedMonthlyBill(),
                    'last_invoice_status' => PartnerInvoice::where('partnership_id', $p->id)->latest()->first()?->status ?? 'none',
                ],
            ];
        });

        return $this->success([
            'partners' => $scores->sortByDesc('health.score')->values(),
            'summary' => [
                'total_active' => $scores->where('status', 'active')->count(),
                'total_pilot' => $scores->where('status', 'pilot')->count(),
                'at_risk' => $scores->where('health.status', 'at_risk')->count(),
                'healthy' => $scores->where('health.status', 'healthy')->count(),
                'expired' => $scores->where('health.status', 'expired')->count(),
            ],
        ]);
    }

    private function calculateHealthScore(LabPartnership $p): array
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sixtyDaysAgo = $now->copy()->subDays(60);

        // Volume trend: compare this 30 days vs previous 30 days
        $currentPeriod = PartnerInterpretation::where('partnership_id', $p->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', $thirtyDaysAgo)->count();

        $previousPeriod = PartnerInterpretation::where('partnership_id', $p->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])->count();

        $trendPct = $previousPeriod > 0
            ? round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100)
            : 100;

        // Days since last activity
        $lastActivity = PartnerInterpretation::where('partnership_id', $p->id)
            ->latest()->first();

        $daysSinceActivity = $lastActivity
            ? $now->diffInDays($lastActivity->created_at)
            : 999;

        // Unpaid invoices
        $unpaidInvoices = PartnerInvoice::where('partnership_id', $p->id)
            ->whereIn('status', ['pending', 'sent', 'overdue'])
            ->count();

        // Determine health status
        $status = 'healthy';
        if ($p->status === 'expired' || $p->status === 'cancelled') {
            $status = 'expired';
        } elseif ($trendPct < -30 || $daysSinceActivity > 30 || $unpaidInvoices > 1) {
            $status = 'at_risk';
        } elseif ($trendPct > 30 && $daysSinceActivity < 7 && $unpaidInvoices === 0) {
            $status = 'healthy';
        }

        $score = max(0, min(100,
            60 + ($trendPct * 0.3) - ($daysSinceActivity * 2) - ($unpaidInvoices * 10)
        ));

        $signals = [];
        if ($trendPct < -20) $signals[] = 'Volume declining (' . $trendPct . '%)';
        if ($daysSinceActivity > 14) $signals[] = 'No activity in ' . $daysSinceActivity . ' days';
        if ($unpaidInvoices > 0) $signals[] = $unpaidInvoices . ' unpaid invoice(s)';
        if ($daysSinceActivity > 30) $signals[] = '⛔ CHURN RISK: inactive 30+ days';

        return [
            'score' => (int) $score,
            'status' => $status,
            'monthly_volume' => $currentPeriod,
            'volume_trend_pct' => $trendPct,
            'days_since_activity' => $daysSinceActivity,
            'unpaid_invoices' => $unpaidInvoices,
            'warning_signals' => $signals,
        ];
    }

    // ── Partnership Stats ──────────────────────────────

    public function stats($id)
    {
        $partnership = LabPartnership::with('provider:id,name')->findOrFail($id);

        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $thisMonthCount = PartnerInterpretation::where('partnership_id', $id)
            ->where('created_at', '>=', $thisMonth)->count();
        $lastMonthCount = PartnerInterpretation::where('partnership_id', $id)
            ->whereBetween('created_at', [$lastMonth, $thisMonth])->count();
        $totalCost = PartnerInterpretation::where('partnership_id', $id)
            ->where('created_at', '>=', $thisMonth)->sum('cost_to_partner');

        $dailyCounts = PartnerInterpretation::where('partnership_id', $id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')->orderBy('date')->get();

        return $this->success([
            'partnership' => $partnership,
            'stats' => [
                'this_month' => $thisMonthCount,
                'last_month' => $lastMonthCount,
                'total_cost_naira' => round($totalCost / 100, 2),
                'estimated_bill' => $partnership->estimatedMonthlyBill(),
            ],
            'daily_counts' => $dailyCounts,
        ]);
    }
}