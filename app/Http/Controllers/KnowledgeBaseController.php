<?php

namespace App\Http\Controllers;

use App\Models\KbArticle;
use App\Models\KbCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request): Response
    {
        $isPrivileged = auth()->user()->hasAnyRole(['admin', 'manager']);
        $search       = $request->query('q', '');

        $categoriesQuery = KbCategory::with([
            $isPrivileged ? 'articles' : 'publishedArticles',
        ])->orderBy('sort_order')->orderBy('name');

        if ($search) {
            $categoriesQuery->whereHas('articles', function ($q) use ($search, $isPrivileged) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('title', 'like', "%{$search}%")
                       ->orWhere('excerpt', 'like', "%{$search}%");
                });
                if (! $isPrivileged) {
                    $q->where('is_published', true);
                }
            });
        }

        $categories = $categoriesQuery->get()->map(function ($cat) use ($isPrivileged) {
            $articles = $isPrivileged ? $cat->articles : $cat->publishedArticles;
            return [
                'id'          => $cat->id,
                'name'        => $cat->name,
                'slug'        => $cat->slug,
                'icon'        => $cat->icon,
                'description' => $cat->description,
                'sort_order'  => $cat->sort_order,
                'articles'    => $articles->map(fn ($a) => [
                    'id'           => $a->id,
                    'title'        => $a->title,
                    'slug'         => $a->slug,
                    'excerpt'      => $a->excerpt,
                    'is_published' => $a->is_published,
                    'sort_order'   => $a->sort_order,
                    'updated_at'   => $a->updated_at?->toISOString(),
                ]),
            ];
        });

        return Inertia::render('KnowledgeBase/Index', [
            'categories'   => $categories,
            'isPrivileged' => $isPrivileged,
            'search'       => $search,
        ]);
    }

    public function show(KbCategory $category, KbArticle $article): Response
    {
        $isPrivileged = auth()->user()->hasAnyRole(['admin', 'manager']);

        if (! $isPrivileged) {
            abort_unless($article->is_published, 404);
        }
        abort_unless($article->category_id === $category->id, 404);

        $siblings = ($isPrivileged ? $category->articles : $category->publishedArticles)
            ->map(fn ($a) => [
                'id'    => $a->id,
                'title' => $a->title,
                'slug'  => $a->slug,
            ]);

        return Inertia::render('KnowledgeBase/Show', [
            'category'     => [
                'id'   => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'article'      => [
                'id'           => $article->id,
                'title'        => $article->title,
                'slug'         => $article->slug,
                'content'      => $article->content,
                'is_published' => $article->is_published,
                'author'       => $article->author?->name,
                'updated_at'   => $article->updated_at?->toISOString(),
            ],
            'siblings'     => $siblings,
            'isPrivileged' => $isPrivileged,
        ]);
    }

    // ── Admin / Manager CRUD ─────────────────────────────────────────────────

    public function storeCategory(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'icon'        => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $slug = Str::slug($data['name']);
        $base = $slug;
        $i    = 1;
        while (KbCategory::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $maxOrder = KbCategory::max('sort_order') ?? 0;

        KbCategory::create([
            ...$data,
            'slug'       => $slug,
            'sort_order' => $maxOrder + 1,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Category created.');
    }

    public function updateCategory(Request $request, KbCategory $category): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'icon'        => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $category->update($data);

        return back()->with('success', 'Category updated.');
    }

    public function destroyCategory(KbCategory $category): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $category->delete();

        return redirect()->route('kb.index')->with('success', 'Category deleted.');
    }

    public function storeArticle(Request $request, KbCategory $category): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $data = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $slug = Str::slug($data['title']);
        $base = $slug;
        $i    = 1;
        while (KbArticle::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $maxOrder = $category->articles()->max('sort_order') ?? 0;

        $article = KbArticle::create([
            'category_id' => $category->id,
            'title'       => $data['title'],
            'slug'        => $slug,
            'content'     => $data['content'],
            'excerpt'     => Str::limit(strip_tags($data['content']), 200),
            'is_published'=> false,
            'sort_order'  => $maxOrder + 1,
            'author_id'   => auth()->id(),
            'updated_by'  => auth()->id(),
        ]);

        return redirect()->route('kb.show', [$category->slug, $article->slug])
            ->with('success', 'Article created.');
    }

    public function updateArticle(Request $request, KbCategory $category, KbArticle $article): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);
        abort_unless($article->category_id === $category->id, 404);

        $data = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $article->update([
            'title'      => $data['title'],
            'content'    => $data['content'],
            'excerpt'    => Str::limit(strip_tags($data['content']), 200),
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Article saved.');
    }

    public function toggleArticle(KbCategory $category, KbArticle $article): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);
        abort_unless($article->category_id === $category->id, 404);

        $article->update(['is_published' => ! $article->is_published]);

        return back()->with('success', $article->is_published ? 'Article published.' : 'Article unpublished.');
    }

    public function destroyArticle(KbCategory $category, KbArticle $article): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);
        abort_unless($article->category_id === $category->id, 404);

        $article->delete();

        return redirect()->route('kb.index')->with('success', 'Article deleted.');
    }
}
