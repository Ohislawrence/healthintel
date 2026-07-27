@extends('layouts.frontend')

@section('title', 'HealthIntel Blog — Health tips, insights, and guides')
@section('description', 'Read the latest health tips, insights, and guides from HealthIntel. Understand lab results, symptoms, and wellness advice in plain language.')

@section('content')
{{-- Blog Header --}}
<section class="section" style="padding-bottom: 40px;">
    <div class="wrap">
        <div class="section-header center">
            <span class="eyebrow anim-fade-up">Blog</span>
            <h1 class="anim-fade-up d1">HealthIntel Blog</h1>
            <p class="anim-fade-up d2">Insights, tips, and guides for understanding your health</p>
        </div>

        {{-- Search --}}
        <div class="text-center anim-fade-up d2" style="max-width: 480px; margin: 0 auto;">
            <form action="{{ route('blog') }}" method="GET" style="display: flex; gap: 8px;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search articles..." style="flex: 1; padding: 12px 16px; border: 1px solid var(--line); border-radius: var(--radius-sm); font-size: 0.9rem; background: var(--paper-raised); color: var(--text); font-family: 'Inter', sans-serif;">
                <button type="submit" class="btn btn-primary btn-sm" style="padding: 12px 20px;">Search</button>
            </form>
        </div>
    </div>
</section>

{{-- Blog Content --}}
<section class="section" style="padding-top: 0;">
    <div class="wrap">
        <div style="display: flex; gap: 40px; align-items: flex-start;">
            {{-- Main Content --}}
            <div style="flex: 1; min-width: 0;">
                @if($posts->count() > 0)
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
                        @foreach($posts as $post)
                            <a href="{{ route('blog.detail', $post->slug) }}" style="display: block; background: var(--paper-raised); border: 1px solid var(--line); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-card); transition: all 0.3s var(--ease-smooth); text-decoration: none; color: inherit;" onmouseover="this.style.borderColor='var(--primary)'; this.style.boxShadow='var(--shadow-elevated)'" onmouseout="this.style.borderColor='var(--line)'; this.style.boxShadow='var(--shadow-card)'">
                                @if($post->featured_image)
                                    <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" style="width: 100%; height: 200px; object-fit: cover;">
                                @endif
                                <div style="padding: 24px;">
                                    @if($post->category)
                                        <span style="display: inline-block; background: var(--primary-light); color: var(--primary-deep); padding: 4px 12px; border-radius: var(--radius-pill); font-size: 0.72rem; font-weight: 600; margin-bottom: 12px;">
                                            {{ $post->category->name }}
                                        </span>
                                    @endif
                                    <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--ink); margin-bottom: 8px; line-height: 1.3; font-family: 'Fraunces', serif;">
                                        {{ $post->title }}
                                    </h3>
                                    @if($post->excerpt)
                                        <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 16px;">
                                            {{ $post->excerpt }}
                                        </p>
                                    @endif
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: var(--text-muted);">
                                        <span>{{ $post->published_at->format('M j, Y') }}</span>
                                        <span>{{ $post->author->name ?? 'HealthIntel' }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if($posts->lastPage() > 1)
                        <div style="display: flex; justify-content: center; gap: 8px; margin-top: 40px;">
                            @for($i = 1; $i <= $posts->lastPage(); $i++)
                                <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}" style="display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; text-decoration: none; transition: all 0.15s; {{ $i === $posts->currentPage() ? 'background: var(--primary); color: #fff;' : 'background: var(--paper-raised); color: var(--text); border: 1px solid var(--line);' }}">
                                    {{ $i }}
                                </a>
                            @endfor
                        </div>
                    @endif
                @else
                    <div class="text-center" style="padding: 60px 20px;">
                        <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 8px;">No posts found.</p>
                        <p style="font-size: 0.9rem; color: var(--text-muted);">Check back soon for new articles.</p>
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <aside style="width: 250px; flex-shrink: 0; display: none;">
                <style>@media(min-width:1024px){aside{display:block !important}}</style>
                <div style="position: sticky; top: 90px; background: var(--paper-raised); border: 1px solid var(--line); border-radius: var(--radius-md); padding: 24px;">
                    <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--ink); margin-bottom: 16px; font-family: 'Fraunces', serif;">Categories</h4>
                    <nav style="display: flex; flex-direction: column; gap: 2px;">
                        <a href="{{ route('blog') }}" style="display: block; padding: 8px 12px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 500; text-decoration: none; transition: all 0.15s; {{ !request('category') ? 'background: var(--primary-light); color: var(--primary-deep);' : 'color: var(--text-muted);' }}" onmouseover="if(!this.classList.contains('bg')){this.style.background='var(--paper)'}" onmouseout="if(!this.classList.contains('bg')){this.style.background='transparent'}">
                            All Posts
                        </a>
                        @foreach($categories as $cat)
                            <a href="{{ route('blog', ['category' => $cat->slug]) }}" style="display: flex; justify-content: space-between; padding: 8px 12px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 500; text-decoration: none; transition: all 0.15s; {{ request('category') === $cat->slug ? 'background: var(--primary-light); color: var(--primary-deep);' : 'color: var(--text-muted);' }}" onmouseover="if(!this.classList.contains('bg')){this.style.background='var(--paper)'}" onmouseout="if(!this.classList.contains('bg')){this.style.background='transparent'}">
                                <span>{{ $cat->name }}</span>
                                <span style="font-size: 0.72rem; opacity: 0.6;">{{ $cat->published_posts_count }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection