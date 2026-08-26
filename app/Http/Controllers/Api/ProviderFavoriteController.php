<?php

namespace App\Http\Controllers\Api;

use App\Models\ProviderDirectoryEntry;
use App\Models\ProviderFavorite;
use Illuminate\Http\Request;

class ProviderFavoriteController extends BaseController
{
    /**
     * List the authenticated user's favorited providers.
     */
    public function index(Request $request)
    {
        $providers = $request->user()->providerFavorites()
            ->whereHas('provider', fn ($q) => $q->where('is_active', true))
            ->with(['provider.locations'])
            ->latest()
            ->get()
            ->map(function (ProviderFavorite $fav) {
                $p = $fav->provider;
                $p->append(['is_sponsored', 'is_open_now']);
                return $p;
            });

        return $this->success(['providers' => $providers]);
    }

    /**
     * Check favorite status for a single provider.
     */
    public function check(Request $request, string $slug)
    {
        $provider = ProviderDirectoryEntry::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $isFavorited = $request->user()->providerFavorites()
            ->where('provider_id', $provider->id)
            ->exists();

        return $this->success(['is_favorited' => $isFavorited]);
    }

    /**
     * Toggle favorite status on/off for a provider.
     */
    public function toggle(Request $request, string $slug)
    {
        $provider = ProviderDirectoryEntry::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $existing = $request->user()->providerFavorites()
            ->where('provider_id', $provider->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return $this->success(['is_favorited' => false], 'Removed from favorites');
        }

        $request->user()->providerFavorites()->create([
            'provider_id' => $provider->id,
        ]);

        return $this->success(['is_favorited' => true], 'Added to favorites', 201);
    }
}