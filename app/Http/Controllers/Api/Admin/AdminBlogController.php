<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Api\BaseController;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminBlogController extends BaseController
{
    // ── Posts ────────────────────────────────────────────

    public function posts()
    {
        $query = BlogPost::with(['category', 'author:id,name']);

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($category = request('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $category));
        }

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $posts = $query->orderByDesc('created_at')->paginate(20);

        return $this->success([
            'posts' => $posts->through(fn($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'featured_image' => static::normalizeImageUrl($post->featured_image),
                'status' => $post->status,
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
                'created_at' => $post->created_at->toISOString(),
                'updated_at' => $post->updated_at->toISOString(),
            ]),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function postShow($id)
    {
        $post = BlogPost::with(['category', 'author:id,name'])->findOrFail($id);

        return $this->success([
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'featured_image' => static::normalizeImageUrl($post->featured_image),
                'category_id' => $post->category_id,
                'category' => $post->category ? [
                    'id' => $post->category->id,
                    'name' => $post->category->name,
                    'slug' => $post->category->slug,
                ] : null,
                'author' => $post->author ? [
                    'id' => $post->author->id,
                    'name' => $post->author->name,
                ] : null,
                'status' => $post->status,
                'published_at' => $post->published_at?->toISOString(),
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'created_at' => $post->created_at->toISOString(),
                'updated_at' => $post->updated_at->toISOString(),
            ],
        ]);
    }

    public function postStore(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'nullable|string|max:2048',
            'category_id' => 'nullable|exists:blog_categories,id',
            'status' => 'required|in:draft,published',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
        ]);

        $data['author_id'] = auth()->id();

        // Ensure excerpt fits DB column (defense-in-depth)
        if (!empty($data['excerpt'])) {
            $data['excerpt'] = \Illuminate\Support\Str::limit($data['excerpt'], 500, '');
        }

        if ($data['status'] === 'published') {
            $data['published_at'] = now();
        }

        $post = BlogPost::create($data);

        return $this->success([
            'post' => ['id' => $post->id, 'slug' => $post->slug],
        ], 'Post created successfully', 201);
    }

    public function postUpdate(Request $request, $id)
    {
        $post = BlogPost::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('blog_posts', 'slug')->ignore($post->id)],
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'nullable|string|max:2048',
            'category_id' => 'nullable|exists:blog_categories,id',
            'status' => 'required|in:draft,published',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
        ]);

        // Ensure excerpt fits DB column (defense-in-depth)
        if (!empty($data['excerpt'])) {
            $data['excerpt'] = \Illuminate\Support\Str::limit($data['excerpt'], 500, '');
        }

        if ($data['status'] === 'published' && !$post->published_at) {
            $data['published_at'] = now();
        }

        $post->update($data);

        return $this->success([
            'post' => ['id' => $post->id, 'slug' => $post->slug],
        ], 'Post updated successfully');
    }

    public function postDelete($id)
    {
        $post = BlogPost::findOrFail($id);
        $post->delete();

        return $this->success(null, 'Post deleted successfully');
    }

    // ── Categories ───────────────────────────────────────

    public function categories()
    {
        $categories = BlogCategory::withCount('posts')
            ->withCount('publishedPosts')
            ->orderBy('name')
            ->get();

        return $this->success(['categories' => $categories]);
    }

    public function categoryStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:blog_categories,name',
        ]);

        $category = BlogCategory::create([
            'name' => $data['name'],
            'slug' => \Illuminate\Support\Str::slug($data['name']),
        ]);

        return $this->success(['category' => $category], 'Category created', 201);
    }

    public function categoryUpdate(Request $request, $id)
    {
        $category = BlogCategory::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('blog_categories', 'name')->ignore($category->id)],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => \Illuminate\Support\Str::slug($data['name']),
        ]);

        return $this->success(['category' => $category], 'Category updated');
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $path = $request->file('image')->store('blog-images', 'public');

        return $this->success([
            'url' => '/storage/' . $path,
        ], 'Image uploaded');
    }

    private static function normalizeImageUrl(?string $url): ?string
    {
        if (!$url) return null;
        if (str_starts_with($url, 'http://localhost')) {
            return preg_replace('#^https?://[^/]+#', '', $url);
        }
        $appUrl = config('app.url');
        if ($appUrl && str_starts_with($url, $appUrl)) {
            return preg_replace('#^' . preg_quote($appUrl, '#') . '#', '', $url);
        }
        return $url;
    }

    public function categoryDelete($id)
    {
        $category = BlogCategory::findOrFail($id);

        if ($category->posts()->count() > 0) {
            return $this->error('Cannot delete category with existing posts. Reassign posts first.', 422);
        }

        $category->delete();

        return $this->success(null, 'Category deleted');
    }
}