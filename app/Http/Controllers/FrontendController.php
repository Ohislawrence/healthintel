<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BlogCategory;
use App\Models\BlogPost;

class FrontendController extends Controller
{
    public function home()
    {
        return view('frontend.home');
    }

    public function about()
    {
        return view('frontend.about');
    }

    public function howItWorks()
    {
        return view('frontend.how-it-works');
    }

    public function features()
    {
        return view('frontend.features');
    }

    public function clinicalValidation()
    {
        $benchmarks = \App\Models\BenchmarkRun::where('status', 'completed')
            ->latest('completed_at')
            ->limit(5)
            ->get();

        $latest = $benchmarks->first();

        return view('frontend.clinical-validation', compact('benchmarks', 'latest'));
    }

    public function forIndividuals()
    {
        return view('frontend.for-individuals');
    }

    public function forClinicians()
    {
        return view('frontend.for-clinicians');
    }

    public function forLabs()
    {
        return view('frontend.for-labs');
    }

    public function forInsurance()
    {
        return view('frontend.for-insurance');
    }

    public function contact()
    {
        return view('frontend.contact');
    }

    public function listYourBusiness()
    {
        return view('frontend.list-your-business');
    }

    public function affiliates()
    {
        $commission = (int) \App\Models\Setting::getValue('referral.percentage', 10);
        $maxPayouts = (int) \App\Models\Setting::getValue('referral.max_payouts_per_referral', 3);
        $minThreshold = (int) \App\Models\Setting::getValue('referral.min_payout_threshold_naira', 5000);

        return view('frontend.affiliates', compact('commission', 'maxPayouts', 'minThreshold'));
    }

    public function partnerships()
    {
        return view('frontend.partnerships');
    }

    public function privacy()
    {
        return view('frontend.privacy');
    }

    public function terms()
    {
        return view('frontend.terms');
    }

    public function blog()
    {
        $query = BlogPost::published()->with(['category', 'author:id,name']);

        if ($categorySlug = request('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $categorySlug));
        }

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $posts = $query->orderByDesc('published_at')->paginate(12);
        $categories = BlogCategory::withCount('publishedPosts')->orderBy('name')->get();

        return view('frontend.blog', compact('posts', 'categories'));
    }

    public function blogShow($slug)
    {
        $post = BlogPost::published()->where('slug', $slug)
            ->with(['category', 'author:id,name'])
            ->first();

        if (!$post) {
            abort(404);
        }

        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->when($post->category_id, fn($q) => $q->where('category_id', $post->category_id))
            ->with('category')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('frontend.blog-detail', compact('post', 'related'));
    }

    public function partnerPatientResults($slug)
    {
        return view('frontend.partner-patient-results', compact('slug'));
    }

    public function sitemap()
    {
        $pages = [
            ['url' => route('home'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['url' => route('features'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['url' => route('about'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => route('how-it-works'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['url' => route('blog'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['url' => route('clinical-validation'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['url' => route('for-individuals'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => route('for-clinicians'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => route('for-labs'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['url' => route('for-insurance'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => route('partnerships'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['url' => route('list-your-business'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['url' => route('affiliates'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['url' => route('contact'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => route('privacy'), 'priority' => '0.4', 'changefreq' => 'yearly'],
            ['url' => route('terms'), 'priority' => '0.4', 'changefreq' => 'yearly'],
        ];

        // Include published blog posts.
        $posts = BlogPost::published()
            ->orderByDesc('published_at')
            ->get(['slug', 'published_at']);

        foreach ($posts as $post) {
            $pages[] = [
                'url' => route('blog.detail', $post->slug),
                'priority' => '0.6',
                'changefreq' => 'monthly',
                'lastmod' => $post->published_at?->toAtomString(),
            ];
        }

        return response()->view('frontend.sitemap', ['pages' => $pages])
            ->header('Content-Type', 'application/xml');
    }
}