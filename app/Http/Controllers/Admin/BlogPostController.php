<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    private const STATUSES = [
        'draft' => 'Draft',
        'review' => 'Review',
        'published' => 'Published',
        'archived' => 'Archived',
    ];

    public function index(Request $request): View
    {
        $query = BlogPost::query()->ordered();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && array_key_exists($request->status, self::STATUSES)) {
            $query->where('status', $request->status);
        }

        $posts = $query->paginate(10)->withQueryString();

        return view('admin.blog-posts.index', [
            'posts' => $posts,
            'statuses' => self::STATUSES,
            'statusCounts' => BlogPost::query()
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
            'totalCount' => BlogPost::query()->count(),
        ]);
    }

    public function create(): View
    {
        $nextSortOrder = ((int) BlogPost::query()->max('sort_order')) + 1;

        return view('admin.blog-posts.create', [
            'post' => new BlogPost([
                'status' => 'draft',
                'sort_order' => $nextSortOrder,
            ]),
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $this->postAttributes($request);
        $attributes['author_id'] = $request->user()?->id;

        BlogPost::create($attributes);

        return redirect()
            ->route('admin.blog.index')
            ->with('status', 'Blog post created.');
    }

    public function edit(BlogPost $blogPost): View
    {
        return view('admin.blog-posts.edit', [
            'post' => $blogPost,
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, BlogPost $blogPost): RedirectResponse
    {
        $blogPost->update($this->postAttributes($request, $blogPost));

        return redirect()
            ->route('admin.blog.index')
            ->with('status', 'Blog post updated.');
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        $blogPost->delete();

        return redirect()
            ->route('admin.blog.index')
            ->with('status', 'Blog post deleted.');
    }

    /**
     * @return array{title: string, slug: string, excerpt: ?string, body: string, status: string, published_at: ?string, sort_order: int}
     */
    private function postAttributes(Request $request, ?BlogPost $post = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('blog_posts', 'slug')->ignore($post),
            ],
            'excerpt' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $slug = Str::slug($validated['slug'] ?: $validated['title']);
        if ($slug === '') {
            $slug = 'post';
        }

        if (
            BlogPost::query()
                ->when($post, fn ($query) => $query->whereKeyNot($post->getKey()))
                ->where('slug', $slug)
                ->exists()
        ) {
            $base = $slug;
            $suffix = 2;
            while (
                BlogPost::query()
                    ->when($post, fn ($query) => $query->whereKeyNot($post->getKey()))
                    ->where('slug', "{$base}-{$suffix}")
                    ->exists()
            ) {
                $suffix++;
            }
            $slug = "{$base}-{$suffix}";
        }

        return [
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'],
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
            'sort_order' => (int) $validated['sort_order'],
        ];
    }
}
