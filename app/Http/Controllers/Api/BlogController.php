<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Models\BlogCategory;
use App\Models\BlogPost;

class BlogController extends BaseController
{
    /**
     * Public: paginated list of published posts.
     */
    public function index()
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

        return $this->success([
            'posts' => $this->formatPosts($posts),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * Public: single blog post detail.
     */
    public function show(string $slug)
    {
        $post = BlogPost::published()->where('slug', $slug)
            ->with(['category', 'author:id,name'])
            ->first();

        if (!$post) {
            return $this->error('Post not found', 404);
        }

        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->when($post->category_id, fn($q) => $q->where('category_id', $post->category_id))
            ->with('category')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return $this->success([
            'post' => $this->formatPost($post),
            'related' => $related->map(fn($p) => $this->formatPostSummary($p)),
        ]);
    }

    /**
     * Public: all categories with published post counts.
     */
    public function categories()
    {
        $categories = BlogCategory::withCount('publishedPosts')
            ->orderBy('name')
            ->get();

        return $this->success([
            'categories' => $categories,
        ]);
    }

    private function formatPosts($paginator)
    {
        return $paginator->through(fn($post) => $this->formatPostSummary($post));
    }

    private function formatPost($post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'featured_image' => static::normalizeImageUrl($post->featured_image),
            'category' => $post->category ? [
                'id' => $post->category->id,
                'name' => $post->category->name,
                'slug' => $post->category->slug,
            ] : null,
            'author' => $post->author ? [
                'id' => $post->author->id,
                'name' => $post->author->name,
            ] : null,
            'published_at' => $post->published_at?->toISOString(),
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
        ];
    }

    private function formatPostSummary($post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'featured_image' => static::normalizeImageUrl($post->featured_image),
            'category' => $post->category ? [
                'id' => $post->category->id,
                'name' => $post->category->name,
                'slug' => $post->category->slug,
            ] : null,
            'author' => $post->author ? [
                'id' => $post->author->id,
                'name' => $post->author->name,
            ] : null,
            'published_at' => $post->published_at?->toISOString(),
        ];
    }

    private static function normalizeImageUrl(?string $url): ?string
    {
        if (!$url) return null;
        // Strip absolute domain so images work on any domain
        if (str_starts_with($url, 'http://localhost')) {
            return preg_replace('#^https?://[^/]+#', '', $url);
        }
        // If it's already a full URL with production domain, keep as-is
        if (str_starts_with($url, 'https://') || str_starts_with($url, 'http://')) {
            // If it's the same origin, convert to relative
            $appUrl = config('app.url');
            if ($appUrl && str_starts_with($url, $appUrl)) {
                return preg_replace('#^' . preg_quote($appUrl, '#') . '#', '', $url);
            }
        }
        return $url;
    }
}
