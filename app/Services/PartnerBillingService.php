<?php

namespace App\Services;

use App\Models\LabPartnership;
use App\Models\PartnerInvoice;
use App\Models\PartnerInterpretation;

class PartnerBillingService
{
    /**
     * Calculate the billable amount for a partnership in a given period
     * based on the partnership's pricing model.
     */
    public function calculateBill(
        LabPartnership $partnership,
        \Carbon\Carbon $periodStart,
        \Carbon\Carbon $periodEnd,
    ): array {
        $interpretations = PartnerInterpretation::where('partnership_id', $partnership->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->get();

        $total = $interpretations->count();

        if ($total === 0) {
            return [
                'total_interpretations' => 0,
                'included_in_allowance' => 0,
                'billable' => 0,
                'unit_price_kobo' => 0,
                'subtotal_kobo' => 0,
                'total_kobo' => 0,
                'line_items' => [],
            ];
        }

        // Check if partnership is in a free pilot period
        if ($this->isInFreePilot($partnership, $periodStart, $periodEnd, $total)) {
            return [
                'total_interpretations' => $total,
                'included_in_allowance' => $total,
                'billable' => 0,
                'unit_price_kobo' => 0,
                'subtotal_kobo' => 0,
                'total_kobo' => 0,
                'line_items' => $this->buildLineItems($interpretations),
            ];
        }

        $unitPriceKobo = $this->getUnitPrice($partnership, $total);
        $allowance = $partnership->monthly_allowance ?? 0;

        return match ($partnership->pricing_model) {
            'flat_monthly' => $this->calculateFlatMonthly($total, $partnership, $interpretations),
            'volume_tier' => $this->calculateVolumeTier($total, $interpretations, $partnership),
            'per_report' => $this->calculatePerReport($total, $allowance, $unitPriceKobo, $partnership, $interpretations),
            default => $this->calculatePerReport($total, $allowance, $unitPriceKobo, $partnership, $interpretations),
        };
    }

    /**
     * Generate an invoice for a partnership for the current month.
     */
    public function generateInvoice(LabPartnership $partnership): PartnerInvoice
    {
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        $bill = $this->calculateBill($partnership, $periodStart, $periodEnd);

        $invoiceNumber = $this->generateInvoiceNumber($partnership);

        return PartnerInvoice::create([
            'partnership_id' => $partnership->id,
            'invoice_number' => $invoiceNumber,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'total_interpretations' => $bill['total_interpretations'],
            'included_in_allowance' => $bill['included_in_allowance'],
            'billable_interpretations' => $bill['billable'],
            'unit_price_kobo' => $bill['unit_price_kobo'],
            'subtotal_kobo' => $bill['subtotal_kobo'],
            'discount_kobo' => 0,
            'total_kobo' => $bill['total_kobo'],
            'line_items' => $bill['line_items'],
            'status' => 'pending',
            'due_date' => now()->addDays(14),
        ]);
    }

    /**
     * Generate invoices for all active partnerships.
     *
     * @return PartnerInvoice[]
     */
    public function generateAllMonthlyInvoices(): array
    {
        $partnerships = LabPartnership::whereIn('status', ['active', 'pilot'])->get();
        $invoices = [];

        foreach ($partnerships as $p) {
            // Skip if invoice already exists for this month
            $exists = PartnerInvoice::where('partnership_id', $p->id)
                ->where('period_start', now()->startOfMonth()->toDateString())
                ->exists();

            if (!$exists) {
                $invoices[] = $this->generateInvoice($p);
            }
        }

        return $invoices;
    }

    // ── Private Pricing Logic ────────────────────────────

    private function isInFreePilot(
        LabPartnership $p,
        \Carbon\Carbon $periodStart,
        \Carbon\Carbon $periodEnd,
        int $total,
    ): bool {
        if ($p->plan_tier !== 'pilot') return false;

        // Free for first 3 months OR first 1,000 reports (whichever runs out first)
        $contractStart = $p->contract_start ? \Carbon\Carbon::parse($p->contract_start) : $p->created_at;
        $monthsSinceStart = $contractStart->diffInMonths($periodEnd);

        if ($monthsSinceStart >= 3) return false;

        // Check cumulative reports since contract start
        $lifetimeTotal = PartnerInterpretation::where('partnership_id', $p->id)
            ->where('status', 'completed')
            ->count();

        return $lifetimeTotal <= 1000;
    }

    private function getUnitPrice(LabPartnership $p, int $total): int
    {
        $baseRate = $p->rate_per_report ?? 0;

        if ($p->pricing_model === 'volume_tier') {
            // Volume tier pricing
            if ($total <= 500) return 30000;       // ₦300
            if ($total <= 2000) return 20000;       // ₦200
            return 15000;                            // ₦150
        }

        return $baseRate;
    }

    private function calculatePerReport(
        int $total,
        int $allowance,
        int $unitPriceKobo,
        LabPartnership $p,
        $interpretations,
    ): array {
        $included = min($total, $allowance);
        $billable = max(0, $total - $allowance);

        // Use overage rate if set and billable exceeds allowance
        if ($p->overage_rate && $billable > 0) {
            $unitPriceKobo = $p->overage_rate;
        }

        $subtotal = $billable * $unitPriceKobo;

        return [
            'total_interpretations' => $total,
            'included_in_allowance' => $included,
            'billable' => $billable,
            'unit_price_kobo' => $unitPriceKobo,
            'subtotal_kobo' => $subtotal,
            'total_kobo' => $subtotal,
            'line_items' => $this->buildLineItems($interpretations),
        ];
    }

    private function calculateVolumeTier(
        int $total,
        $interpretations,
        LabPartnership $p,
    ): array {
        // Volume tier: all reports charged at tier rate based on total volume
        $unitPriceKobo = $this->getUnitPrice($p, $total);
        $subtotal = $total * $unitPriceKobo;

        return [
            'total_interpretations' => $total,
            'included_in_allowance' => 0,
            'billable' => $total,
            'unit_price_kobo' => $unitPriceKobo,
            'subtotal_kobo' => $subtotal,
            'total_kobo' => $subtotal,
            'line_items' => $this->buildLineItems($interpretations),
        ];
    }

    private function calculateFlatMonthly(
        int $total,
        LabPartnership $p,
        $interpretations,
    ): array {
        $subtotal = $p->rate_per_report ?? 0; // rate_per_report = monthly flat fee in kobo

        return [
            'total_interpretations' => $total,
            'included_in_allowance' => $total,
            'billable' => 0,
            'unit_price_kobo' => 0,
            'subtotal_kobo' => $subtotal,
            'total_kobo' => $subtotal,
            'line_items' => $this->buildLineItems($interpretations),
        ];
    }

    private function buildLineItems($interpretations): array
    {
        $breakdown = [];
        foreach ($interpretations as $i) {
            $key = $i->test_name;
            if (!isset($breakdown[$key])) {
                $breakdown[$key] = ['test_name' => $key, 'count' => 0, 'total_cost_kobo' => 0];
            }
            $breakdown[$key]['count']++;
            $breakdown[$key]['total_cost_kobo'] += $i->cost_to_partner;
        }
        return array_values($breakdown);
    }

    private function generateInvoiceNumber(LabPartnership $p): string
    {
        $prefix = 'INV-' . now()->format('Ym');
        $count = PartnerInvoice::where('invoice_number', 'like', $prefix . '-%')->count();
        return $prefix . '-' . str_pad((string) ($count + 1 + $p->id), 3, '0', STR_PAD_LEFT);
    }
}