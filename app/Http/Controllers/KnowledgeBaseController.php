<?php

namespace App\Http\Controllers;

use App\Models\KbArticle;
use App\Models\KbCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KnowledgeBaseController extends Controller
{
    // All defined roles with display labels
    const ROLES = [
        'admin'     => 'Admin',
        'manager'   => 'Manager',
        'hr'        => 'HR',
        'site_head' => 'Site Head',
        'staff'     => 'Staff',
    ];

    public function index(Request $request): Response
    {
        $user         = auth()->user();
        $isPrivileged = $user->hasAnyRole(['admin', 'manager']);
        $search       = $request->query('q', '');
        $userRoles    = $user->getRoleNames()->toArray();

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

        $categories = $categoriesQuery->get()->map(function ($cat) use ($isPrivileged, $user) {
            $articles = ($isPrivileged ? $cat->articles : $cat->publishedArticles)
                ->filter(fn ($a) => $a->isVisibleTo($user))
                ->values();

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
                    'visible_to'   => $a->visible_to ?? [],
                    'sort_order'   => $a->sort_order,
                    'updated_at'   => $a->updated_at?->toISOString(),
                ]),
            ];
        });

        return Inertia::render('KnowledgeBase/Index', [
            'categories'     => $categories,
            'isPrivileged'   => $isPrivileged,
            'search'         => $search,
            'availableRoles' => self::ROLES,
        ]);
    }

    public function show(string $categorySlug, string $articleSlug): Response
    {
        $category     = KbCategory::where('slug', $categorySlug)->firstOrFail();
        $article      = KbArticle::where('slug', $articleSlug)->where('category_id', $category->id)->firstOrFail();
        $user         = auth()->user();
        $isPrivileged = $user->hasAnyRole(['admin', 'manager']);

        if (! $isPrivileged) {
            abort_unless($article->is_published, 404);
        }
        abort_unless($article->isVisibleTo($user), 403);

        $siblings = ($isPrivileged ? $category->articles : $category->publishedArticles)
            ->filter(fn ($a) => $a->isVisibleTo($user))
            ->map(fn ($a) => [
                'id'    => $a->id,
                'title' => $a->title,
                'slug'  => $a->slug,
            ])->values();

        return Inertia::render('KnowledgeBase/Show', [
            'category'       => [
                'id'   => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'article'        => [
                'id'           => $article->id,
                'title'        => $article->title,
                'slug'         => $article->slug,
                'content'      => $article->content,
                'is_published' => $article->is_published,
                'visible_to'   => $article->visible_to ?? [],
                'author'       => $article->author?->name,
                'updated_at'   => $article->updated_at?->toISOString(),
            ],
            'siblings'       => $siblings,
            'isPrivileged'   => $isPrivileged,
            'availableRoles' => self::ROLES,
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
            'title'      => ['required', 'string', 'max:255'],
            'content'    => ['required', 'string'],
            'visible_to' => ['nullable', 'array'],
            'visible_to.*' => ['string', 'in:' . implode(',', array_keys(self::ROLES))],
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
            'visible_to'  => empty($data['visible_to']) ? null : $data['visible_to'],
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
            'title'        => ['required', 'string', 'max:255'],
            'content'      => ['required', 'string'],
            'visible_to'   => ['nullable', 'array'],
            'visible_to.*' => ['string', 'in:' . implode(',', array_keys(self::ROLES))],
        ]);

        $article->update([
            'title'      => $data['title'],
            'content'    => $data['content'],
            'excerpt'    => Str::limit(strip_tags($data['content']), 200),
            'visible_to' => empty($data['visible_to']) ? null : $data['visible_to'],
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

    // ── Media upload & serve ─────────────────────────────────────────────────

    public function upload(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $request->validate([
            'file' => ['required', 'file', 'max:512000',
                'mimes:jpg,jpeg,png,gif,webp,svg,mp4,mov,webm,avi,mkv,m4v'],
        ]);

        $file = $request->file('file');
        $type = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image';
        $disk = $this->mediaDisk();
        $path = $file->store("kb/{$type}s", $disk);

        return response()->json([
            'url'  => route('kb.media', $path),
            'type' => $type,
        ]);
    }

    public function serveMedia(Request $request, string $path): StreamedResponse|\Illuminate\Http\Response
    {
        $useR2 = (bool) config('filesystems.disks.r2.bucket');
        $disk  = $useR2 ? 'r2' : 'public';

        abort_unless(Storage::disk($disk)->exists($path), 404);

        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            'svg'         => 'image/svg+xml',
            'mp4', 'm4v'  => 'video/mp4',
            'mov'         => 'video/quicktime',
            'webm'        => 'video/webm',
            'avi'         => 'video/x-msvideo',
            'mkv'         => 'video/x-matroska',
            default       => 'application/octet-stream',
        };

        $isVideo = str_starts_with($mime, 'video/');

        if ($useR2) {
            $client = new \Aws\S3\S3Client([
                'region'                  => 'auto',
                'endpoint'                => config('filesystems.disks.r2.endpoint'),
                'credentials'             => [
                    'key'    => config('filesystems.disks.r2.key'),
                    'secret' => config('filesystems.disks.r2.secret'),
                ],
                'use_path_style_endpoint' => true,
                'version'                 => 'latest',
            ]);

            if ($isVideo) {
                $size   = Storage::disk('r2')->size($path);
                $start  = 0; $end = $size - 1; $status = 200;

                if ($range = $request->header('Range')) {
                    if (preg_match('/bytes=(\d+)-(\d*)/', $range, $m)) {
                        $start  = (int) $m[1];
                        $end    = $m[2] !== '' ? min((int) $m[2], $size - 1) : $size - 1;
                        $status = 206;
                    }
                }
                $length  = $end - $start + 1;
                $headers = [
                    'Content-Type'        => $mime,
                    'Content-Length'      => $length,
                    'Accept-Ranges'       => 'bytes',
                    'Content-Disposition' => 'inline',
                    'Cache-Control'       => 'private, max-age=3600',
                ];
                if ($status === 206) {
                    $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
                }
                $result = $client->getObject([
                    'Bucket' => config('filesystems.disks.r2.bucket'),
                    'Key'    => $path,
                    'Range'  => "bytes={$start}-{$end}",
                ]);
                $body = $result['Body'];
                return response()->stream(function () use ($body) {
                    while (! $body->eof()) { echo $body->read(256 * 1024); flush(); }
                }, $status, $headers);
            }

            // Image — stream full content
            $result = $client->getObject([
                'Bucket' => config('filesystems.disks.r2.bucket'),
                'Key'    => $path,
            ]);
            $body = $result['Body'];
            return response()->stream(function () use ($body) {
                while (! $body->eof()) { echo $body->read(64 * 1024); flush(); }
            }, 200, [
                'Content-Type'        => $mime,
                'Content-Disposition' => 'inline',
                'Cache-Control'       => 'private, max-age=3600',
            ]);
        }

        // Local public disk
        $filePath = Storage::disk('public')->path($path);
        abort_unless(file_exists($filePath), 404);

        if ($isVideo) {
            $size   = filesize($filePath);
            $start  = 0; $end = $size - 1; $status = 200;

            if ($range = $request->header('Range')) {
                if (preg_match('/bytes=(\d+)-(\d*)/', $range, $m)) {
                    $start  = (int) $m[1];
                    $end    = $m[2] !== '' ? min((int) $m[2], $size - 1) : $size - 1;
                    $status = 206;
                }
            }
            $length  = $end - $start + 1;
            $headers = [
                'Content-Type'        => $mime,
                'Content-Length'      => $length,
                'Accept-Ranges'       => 'bytes',
                'Content-Disposition' => 'inline',
                'Cache-Control'       => 'private, max-age=3600',
            ];
            if ($status === 206) {
                $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
            }
            return response()->stream(function () use ($filePath, $start, $length) {
                $fp        = fopen($filePath, 'rb');
                $remaining = $length;
                fseek($fp, $start);
                while ($remaining > 0 && ! feof($fp)) {
                    $chunk      = min(256 * 1024, $remaining);
                    $remaining -= $chunk;
                    echo fread($fp, $chunk);
                    flush();
                }
                fclose($fp);
            }, $status, $headers);
        }

        return response()->file($filePath, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }

    private function mediaDisk(): string
    {
        return config('filesystems.disks.r2.bucket') ? 'r2' : 'public';
    }
}
