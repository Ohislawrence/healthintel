@extends('layouts.frontend')

@section('title', $post->meta_title ?: $post->title . ' — HealthIntel Blog')
@section('description', $post->meta_description ?: $post->excerpt ?: 'Read this article on the HealthIntel Blog.')

@section('content')
{{-- Breadcrumb --}}
<section style="padding: 24px 0 0 0;">
    <div class="wrap">
        <nav style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-muted);">
            <a href="{{ route('blog') }}" style="color: var(--primary); text-decoration: none; font-weight: 500;">Blog</a>
            <span>/</span>
            @if($post->category)
                <a href="{{ route('blog', ['category' => $post->category->slug]) }}" style="color: var(--primary); text-decoration: none; font-weight: 500;">{{ $post->category->name }}</a>
                <span>/</span>
            @endif
            <span style="color: var(--text-muted);">{{ $post->title }}</span>
        </nav>
    </div>
</section>

{{-- Article --}}
<article style="max-width: 780px; margin: 0 auto; padding: 32px 20px 60px;">
    {{-- Featured Image --}}
    @if($post->featured_image)
        <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" style="width: 100%; border-radius: var(--radius-lg); max-height: 400px; object-fit: cover; margin-bottom: 32px;">
    @endif

    {{-- Category Badge --}}
    @if($post->category)
        <span style="display: inline-block; background: var(--primary-light); color: var(--primary-deep); padding: 4px 12px; border-radius: var(--radius-pill); font-size: 0.72rem; font-weight: 600; margin-bottom: 16px;">
            {{ $post->category->name }}
        </span>
    @endif

    {{-- Title --}}
    <h1 style="font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 600; color: var(--ink); line-height: 1.2; margin-bottom: 16px; font-family: 'Fraunces', serif;">
        {{ $post->title }}
    </h1>

    {{-- Meta --}}
    <div style="display: flex; align-items: center; gap: 12px; font-size: 0.9rem; color: var(--text-muted); margin-bottom: 40px;">
        <span>{{ $post->author->name ?? 'HealthIntel' }}</span>
        <span>·</span>
        <span>{{ $post->published_at->format('F j, Y') }}</span>
    </div>

    {{-- Content --}}
    <div style="font-size: 1.05rem; line-height: 1.85; color: var(--text);">
        {!! $post->content !!}

        <style>
            .prose h2 { font-family: 'Fraunces', serif; font-size: 1.5rem; font-weight: 600; color: var(--ink); margin-top: 40px; margin-bottom: 16px; }
            .prose h3 { font-family: 'Fraunces', serif; font-size: 1.2rem; font-weight: 600; color: var(--ink); margin-top: 32px; margin-bottom: 12px; }
            .prose p { margin-bottom: 20px; }
            .prose img { border-radius: var(--radius-md); margin: 24px auto; display: block; }
            .prose a { color: var(--primary); text-decoration: underline; }
            .prose ul, .prose ol { margin-bottom: 20px; padding-left: 24px; }
            .prose li { margin-bottom: 8px; }
            .prose blockquote { border-left: 3px solid var(--primary); background: var(--primary-light); padding: 16px 20px; border-radius: 0 var(--radius-md) var(--radius-md) 0; margin-bottom: 20px; }
            .prose strong { color: var(--ink); }
        </style>
    </div>

    {{-- CTA --}}
    <div style="margin-top: 48px; background: linear-gradient(135deg, var(--primary-light), rgba(185,129,46,0.05)); border: 1px solid rgba(14,107,92,0.15); border-radius: var(--radius-lg); padding: 32px; text-align: center;">
        <h3 style="font-family: 'Fraunces', serif; font-size: 1.2rem; color: var(--ink); margin-bottom: 8px;">Want to understand your lab results?</h3>
        <p style="color: var(--text-muted); margin-bottom: 16px;">Upload your lab report and get a clear explanation in seconds.</p>
        <a href="/register" class="btn btn-primary">Get started free →</a>
    </div>
</article>

{{-- Related Posts --}}
@if($related->count() > 0)
    <section class="section" style="background: var(--paper); border-top: 1px solid var(--line);">
        <div class="wrap">
            <h2 style="font-family: 'Fraunces', serif; font-size: 1.5rem; font-weight: 600; color: var(--ink); margin-bottom: 32px;">Related Articles</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                @foreach($related as $rp)
                    <a href="{{ route('blog.detail', $rp->slug) }}" style="display: block; background: var(--paper-raised); border: 1px solid var(--line); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-card); transition: all 0.3s; text-decoration: none; color: inherit;" onmouseover="this.style.borderColor='var(--primary)'; this.style.boxShadow='var(--shadow-elevated)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='var(--line)'; this.style.boxShadow='var(--shadow-card)'; this.style.transform='none'">
                        @if($rp->featured_image)
                            <img src="{{ $rp->featured_image }}" alt="{{ $rp->title }}" style="width: 100%; height: 160px; object-fit: cover;">
                        @endif
                        <div style="padding: 20px;">
                            @if($rp->category)
                                <span style="display: inline-block; background: var(--primary-light); color: var(--primary-deep); padding: 3px 10px; border-radius: var(--radius-pill); font-size: 0.68rem; font-weight: 600; margin-bottom: 8px;">
                                    {{ $rp->category->name }}
                                </span>
                            @endif
                            <h3 style="font-size: 0.95rem; font-weight: 600; color: var(--ink); line-height: 1.3; font-family: 'Fraunces', serif; margin-bottom: 8px;">
                                {{ $rp->title }}
                            </h3>
                            <p style="font-size: 0.75rem; color: var(--text-muted);">{{ $rp->published_at->format('M j, Y') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
@endsection