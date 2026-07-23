<?php

namespace App\Http\Controllers\Api;

use App\Models\ProviderDirectoryEntry;
use App\Models\ReferralEvent;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PartnerPortalController extends BaseController
{
    public function __construct(
        private ReferralService $referralService,
    ) {}

    /**
     * Provider login via access_code (magic-link style).
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'access_code' => 'required|string|max:64',
        ]);

        $provider = ProviderDirectoryEntry::where('access_code', $validated['access_code'])
            ->where('partner_status', '!=', 'none')
            ->first();

        if (! $provider) {
            return $this->error('Invalid access code or partner not active.', 401);
        }



        // Generate a short-lived provider token (expires in 2 hours)
        $token = $provider->createToken('partner-portal', ['partner-access'], now()->addHours(2))->plainTextToken;

        return $this->success([
            'provider' => $provider->only([
                'id', 'name', 'slug', 'type', 'specialty',
                'phone', 'email',
                'address', 'city', 'state',
                'partner_status',
                'referral_link',
                'is_verified', 'is_active',
            ]),
            'token' => $token,
        ], 'Welcome to your partner dashboard.');
    }

    /**
     * Provider dashboard with referral analytics.
     */
    public function dashboard(Request $request)
    {
        $provider = $this->resolveProvider($request);

        $thirtyDaysAgo = now()->subDays(30);

        $totalReferrals = ReferralEvent::where('provider_id', $provider->id)->count();

        $recentReferrals = ReferralEvent::where('provider_id', $provider->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $referralsByAction = ReferralEvent::where('provider_id', $provider->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('action, count(*) as total')
            ->groupBy('action')
            ->pluck('total', 'action');

        $referralsBySource = ReferralEvent::where('provider_id', $provider->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('source_feature, count(*) as total')
            ->groupBy('source_feature')
            ->pluck('total', 'source_feature');

        $referralsPerDay = ReferralEvent::where('provider_id', $provider->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->success([
            'stats' => [
                'total_referrals' => $totalReferrals,
                'recent_referrals_30d' => $recentReferrals,
                'by_action' => $referralsByAction,
                'by_source' => $referralsBySource,
            ],
            'referrals_per_day' => $referralsPerDay,
        ]);
    }

    /**
     * Update provider listing info.
     */
    public function updateListing(Request $request)
    {
        $provider = $this->resolveProvider($request);

        $validated = $request->validate([
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:200',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'website' => 'nullable|string|max:300',
            'bio' => 'nullable|string|max:2000',
            'specialty' => 'nullable|string|max:200',
            'insurance_plans' => 'nullable|array',
        ]);

        $provider->update($validated);

        return $this->success([
            'provider' => $provider->fresh()->makeVisible(['access_code']),
        ]);
    }

    /**
     * Generate or regenerate an access code.
     */
    public function regenerateAccessCode(Request $request)
    {
        $provider = $this->resolveProvider($request);

        $provider->update([
            'access_code' => Str::random(40),
            'access_code_generated_at' => now(),
        ]);

        return $this->success([
            'access_code' => $provider->access_code,
        ]);
    }

    /**
     * Resolve the provider from the Sanctum token.
     */
    private function resolveProvider(Request $request): ProviderDirectoryEntry
    {
        // The authenticated model is ProviderDirectoryEntry (via Sanctum's HasApiTokens)
        $provider = $request->user();

        if (! $provider instanceof ProviderDirectoryEntry) {
            abort(403, 'Partner access required.');
        }

        return $provider;
    }
}