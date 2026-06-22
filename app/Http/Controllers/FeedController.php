<?php

namespace App\Http\Controllers;

use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedController extends Controller
{
    public function index(Request $request): Response
    {
        $user         = $request->user();
        $isPrivileged = $user->hasAnyRole(['admin', 'manager', 'hr']);

        $posts = Post::with([
                'author:id,name,avatar',
                'recognizedUser:id,name,avatar',
                'reactions.user:id,name',
                'comments.user:id,name,avatar',
            ])
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($post) => $this->payload($post, $user, $isPrivileged));

        // Staff list only needed for the recognition picker (privileged users)
        $staffList = $isPrivileged
            ? User::where('is_active', true)
                ->select('id', 'name', 'avatar')
                ->orderBy('name')
                ->get()
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'avatar_url' => $u->avatar_url])
            : collect();

        return Inertia::render('Feed/Index', [
            'posts'        => $posts,
            'staffList'    => $staffList,
            'isPrivileged' => $isPrivileged,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'type'               => ['required', 'in:' . implode(',', Post::TYPES)],
            'title'              => ['nullable', 'string', 'max:255'],
            'body'               => ['required', 'string', 'max:10000'],
            'images'             => ['nullable', 'array', 'max:6'],
            'images.*'           => ['string', 'max:500', function ($attribute, $value, $fail) {
                if (! str_starts_with($value, url('/knowledge-base/media/'))) {
                    $fail('Invalid image URL.');
                }
            }],
            'event_date'         => ['nullable', 'date', 'required_if:type,event'],
            'event_location'     => ['nullable', 'string', 'max:255'],
            'recognized_user_id' => ['nullable', 'string', 'exists:users,id', 'required_if:type,recognition'],
        ]);

        // Recognition posts (employee of the month etc.) are management-issued
        if ($data['type'] === 'recognition' && ! $user->hasAnyRole(['admin', 'manager', 'hr'])) {
            return back()->with('error', 'Only managers can publish recognition posts.');
        }

        Post::create([
            'user_id'            => $user->id,
            'type'               => $data['type'],
            'title'              => $data['title'] ?? null,
            'body'               => $data['body'],
            'images'             => $data['images'] ?? null,
            'event_date'         => $data['type'] === 'event' ? ($data['event_date'] ?? null) : null,
            'event_location'     => $data['type'] === 'event' ? ($data['event_location'] ?? null) : null,
            'recognized_user_id' => $data['type'] === 'recognition' ? ($data['recognized_user_id'] ?? null) : null,
        ]);

        return back()->with('success', 'Posted to the feed.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $user    = $request->user();
        $isOwner = $post->user_id === $user->id;

        abort_unless($isOwner || $user->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $post->delete();

        return back()->with('success', 'Post deleted.');
    }

    public function pin(Request $request, Post $post): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $post->update(['is_pinned' => ! $post->is_pinned]);

        return back()->with('success', $post->is_pinned ? 'Post pinned.' : 'Post unpinned.');
    }

    public function react(Request $request, Post $post): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:' . implode(',', Post::REACTION_TYPES)],
        ]);

        $user     = $request->user();
        $existing = PostReaction::where('post_id', $post->id)->where('user_id', $user->id)->first();

        if ($existing && $existing->type === $data['type']) {
            // Same reaction again = remove (toggle off)
            $existing->delete();
        } elseif ($existing) {
            // Switch reaction type
            $existing->update(['type' => $data['type']]);
        } else {
            PostReaction::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'type'    => $data['type'],
            ]);
        }

        return back();
    }

    public function comment(Request $request, Post $post): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        PostComment::create([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
        ]);

        return back();
    }

    public function destroyComment(Request $request, Post $post, PostComment $comment): RedirectResponse
    {
        abort_unless($comment->post_id === $post->id, 404);

        $user    = $request->user();
        $isOwner = $comment->user_id === $user->id;

        abort_unless($isOwner || $user->hasAnyRole(['admin', 'manager', 'hr']), 403);

        $comment->delete();

        return back();
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp'],
        ]);

        $disk = config('filesystems.disks.r2.bucket') ? 'r2' : 'public';
        $path = $request->file('file')->store('feed/images', $disk);

        return response()->json([
            'url'  => route('kb.media', $path),
            'path' => $path,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function payload(Post $post, User $viewer, bool $isPrivileged): array
    {
        // Group reactions: ['like' => ['count' => 3, 'names' => [...]], ...]
        $reactionSummary = $post->reactions
            ->groupBy('type')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'names' => $group->map(fn ($r) => $r->user?->name)->filter()->values(),
            ]);

        $myReaction = $post->reactions->firstWhere('user_id', $viewer->id)?->type;

        return [
            'id'              => $post->id,
            'type'            => $post->type,
            'title'           => $post->title,
            'body'            => $post->body,
            'images'          => $post->images ?? [],
            'event_date'      => $post->event_date?->toDateString(),
            'event_location'  => $post->event_location,
            'is_pinned'       => $post->is_pinned,
            'created_at'      => $post->created_at->toIso8601String(),
            'author'          => [
                'id'         => $post->author->id,
                'name'       => $post->author->name,
                'avatar_url' => $post->author->avatar_url,
            ],
            'recognized_user' => $post->recognizedUser ? [
                'id'         => $post->recognizedUser->id,
                'name'       => $post->recognizedUser->name,
                'avatar_url' => $post->recognizedUser->avatar_url,
            ] : null,
            'reactions'       => $reactionSummary,
            'my_reaction'     => $myReaction,
            'reaction_count'  => $post->reactions->count(),
            'article_links'   => $this->articleLinks($post->body, $viewer),
            'comments'        => $post->comments->map(fn ($c) => [
                'id'         => $c->id,
                'body'       => $c->body,
                'created_at' => $c->created_at->toIso8601String(),
                'can_delete' => $c->user_id === $viewer->id || $isPrivileged,
                'user'       => [
                    'id'         => $c->user->id,
                    'name'       => $c->user->name,
                    'avatar_url' => $c->user->avatar_url,
                ],
            ]),
            'can_delete'      => $post->user_id === $viewer->id || $isPrivileged,
        ];
    }

    /**
     * Find Knowledge Base article links inside a post body and resolve them to
     * lightweight reference cards (only published articles the viewer may see).
     */
    private function articleLinks(string $body, User $viewer): array
    {
        if (! preg_match_all('#knowledge-base/([a-z0-9\-]+)/([a-z0-9\-]+)#i', $body, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $links = [];
        $seen  = [];

        foreach ($matches as $m) {
            [$full, $categorySlug, $articleSlug] = $m;
            $key = $categorySlug . '/' . $articleSlug;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $category = KbCategory::where('slug', $categorySlug)->first();
            if (! $category) {
                continue;
            }

            $article = KbArticle::where('slug', $articleSlug)
                ->where('category_id', $category->id)
                ->first();

            if (! $article || ! $article->is_published || ! $article->isVisibleTo($viewer)) {
                continue;
            }

            $links[] = [
                'title'    => $article->title,
                'category' => $category->name,
                'icon'     => $category->icon,
                'url'      => route('kb.show', [$category->slug, $article->slug]),
            ];
        }

        return $links;
    }
}
