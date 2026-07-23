<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

    public function pricing()
    {
        return view('frontend.pricing');
    }

    public function features()
    {
        return view('frontend.features');
    }

    public function contact()
    {
        return view('frontend.contact');
    }

    public function privacy()
    {
        return view('frontend.privacy');
    }

    public function terms()
    {
        return view('frontend.terms');
    }

    public function sitemap()
    {
        $pages = [
            ['url' => route('home'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['url' => route('features'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['url' => route('about'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => route('how-it-works'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['url' => route('pricing'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['url' => route('contact'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => route('privacy'), 'priority' => '0.4', 'changefreq' => 'yearly'],
            ['url' => route('terms'), 'priority' => '0.4', 'changefreq' => 'yearly'],
        ];

        return response()->view('frontend.sitemap', ['pages' => $pages])
            ->header('Content-Type', 'application/xml');
    }
}