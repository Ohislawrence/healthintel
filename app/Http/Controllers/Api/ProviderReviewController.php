<?php

namespace App\Http\Controllers\Api;

use App\Models\ProviderDirectoryEntry;
use App\Models\ProviderReview;
use Illuminate\Http\Request;

class ProviderReviewController extends BaseController
{
    /**
     * List reviews for a provider (public read-only).
     */
    public function index(string $slug)
    {
        $provider = ProviderDirectoryEntry::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $reviews = $provider->reviews()
            ->with('user:id,name')
            ->latest()
            ->get();

        return $this->success([
            'rating_avg' => round($provider->reviews()->avg('rating') ?? 0, 1),
            'review_count' => $provider->reviews()->count(),
            'reviews' => $reviews,
        ]);
    }

    /**
     * Create or update the authenticated user's review for a provider.
     */
    public function store(Request $request, string $slug)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:120',
            'body' => 'required|string|max:2000',
        ]);

        $provider = ProviderDirectoryEntry::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $review = ProviderReview::updateOrCreate(
            [
                'provider_id' => $provider->id,
                'user_id' => $request->user()->id,
            ],
            [
                'rating' => $validated['rating'],
                'title' => $validated['title'] ?? null,
                'body' => $validated['body'],
            ],
        );

        $review->load('user:id,name');

        return $this->success(
            ['review' => $review],
            'Review submitted',
            201,
            [
                'rating_avg' => round($provider->reviews()->avg('rating') ?? 0, 1),
                'review_count' => $provider->reviews()->count(),
            ],
        );
    }

    /**
     * Remove the authenticated user's review.
     */
    public function destroy(Request $request, string $slug)
    {
        $provider = ProviderDirectoryEntry::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $deleted = ProviderReview::where('provider_id', $provider->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        if (!$deleted) {
            return $this->error('You have not reviewed this provider.', 404);
        }

        return $this->success(null, 'Review removed');
    }
}