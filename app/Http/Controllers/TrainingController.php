<?php

namespace App\Http\Controllers;

use App\Models\TrainingLesson;
use App\Models\TrainingModule;
use App\Models\TrainingProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends Controller
{
    // ── Staff views ───────────────────────────────────────────────────────────

    public function index(): Response
    {
        $user         = auth()->user();
        $isPrivileged = $user->hasAnyRole(['admin', 'manager']);

        $modules = TrainingModule::with(['publishedLessons'])
            ->when(! $isPrivileged, fn ($q) => $q->published())
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get()
            ->map(function ($m) use ($user) {
                $lessonIds      = $m->publishedLessons->pluck('id');
                $completedCount = TrainingProgress::where('user_id', $user->id)
                    ->whereIn('lesson_id', $lessonIds)
                    ->whereNotNull('completed_at')
                    ->count();

                return [
                    'id'           => $m->id,
                    'title'        => $m->title,
                    'description'  => $m->description,
                    'thumbnail'    => $m->thumbnail,
                    'is_published' => $m->is_published,
                    'lesson_count' => $m->publishedLessons->count(),
                    'completed'    => $completedCount,
                    'first_lesson' => $m->publishedLessons->first()?->id,
                ];
            });

        return Inertia::render('Training/Index', [
            'modules'      => $modules,
            'isPrivileged' => $isPrivileged,
        ]);
    }

    public function module(TrainingModule $module): Response|RedirectResponse
    {
        $user         = auth()->user();
        $isPrivileged = $user->hasAnyRole(['admin', 'manager']);

        if (! $isPrivileged) {
            abort_unless($module->is_published, 404);
        }

        $first = $isPrivileged
            ? $module->lessons()->orderBy('sort_order')->first()
            : $module->publishedLessons()->first();

        if ($first) {
            return redirect()->route('training.watch', [$module->id, $first->id]);
        }

        abort_unless($isPrivileged, 404);

        return Inertia::render('Training/Watch', [
            'module'       => ['id' => $module->id, 'title' => $module->title],
            'lesson'       => null,
            'curriculum'   => [],
            'isCompleted'  => false,
            'isPrivileged' => true,
        ]);
    }

    public function watch(TrainingModule $module, TrainingLesson $lesson): Response
    {
        $user         = auth()->user();
        $isPrivileged = $user->hasAnyRole(['admin', 'manager']);

        if (! $isPrivileged) {
            abort_unless($module->is_published && $lesson->is_published, 404);
        }
        abort_unless($lesson->module_id === $module->id, 404);

        $lessons = $isPrivileged ? $module->lessons : $module->publishedLessons;

        $completedIds = TrainingProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')
            ->all();

        $curriculum = $lessons->map(fn ($l) => [
            'id'             => $l->id,
            'title'          => $l->title,
            'duration_label' => $l->duration_label,
            'is_published'   => $l->is_published,
            'has_video'      => (bool) $l->video_path,
            'completed'      => in_array($l->id, $completedIds),
        ]);

        return Inertia::render('Training/Watch', [
            'module'      => [
                'id'    => $module->id,
                'title' => $module->title,
            ],
            'lesson'      => [
                'id'               => $lesson->id,
                'title'            => $lesson->title,
                'description'      => $lesson->description,
                'video_url'        => $lesson->video_url,
                'duration_label'   => $lesson->duration_label,
                'duration_seconds' => $lesson->duration_seconds,
                'is_published'     => $lesson->is_published,
                'has_video'        => (bool) $lesson->video_path,
            ],
            'curriculum'   => $curriculum,
            'isCompleted'  => in_array($lesson->id, $completedIds),
            'isPrivileged' => $isPrivileged,
        ]);
    }

    public function updateProgress(Request $request, TrainingLesson $lesson): RedirectResponse
    {
        TrainingProgress::updateOrCreate(
            ['user_id' => auth()->id(), 'lesson_id' => $lesson->id],
            ['completed_at' => $request->boolean('completed') ? now() : null],
        );

        return back()->with('success', $request->boolean('completed') ? 'Lesson marked as complete.' : 'Lesson marked as incomplete.');
    }

    // ── Admin / Manager actions ───────────────────────────────────────────────

    public function storeModule(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $maxOrder = TrainingModule::max('sort_order') ?? 0;

        TrainingModule::create([
            'title'        => $request->title,
            'description'  => $request->description,
            'sort_order'   => $maxOrder + 1,
            'is_published' => false,
        ]);

        return back()->with('success', 'Module created.');
    }

    public function updateModule(Request $request, TrainingModule $module): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $module->update($request->only('title', 'description'));

        return back()->with('success', 'Module updated.');
    }

    public function toggleModule(TrainingModule $module): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $module->update(['is_published' => ! $module->is_published]);

        return back()->with('success', $module->is_published ? 'Module published.' : 'Module unpublished.');
    }

    public function destroyModule(TrainingModule $module): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        foreach ($module->lessons as $lesson) {
            $this->deleteVideo($lesson->video_path);
        }

        $module->delete();

        return redirect()->route('training.index')->with('success', 'Module deleted.');
    }

    public function storeLesson(Request $request, TrainingModule $module): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'video'            => ['nullable', 'file', 'mimes:mp4,mov,avi,webm,mkv,m4v', 'max:512000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $videoPath = null;
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('training/videos', $this->videoDisk());
        }

        $maxOrder = $module->lessons()->max('sort_order') ?? 0;

        $lesson = TrainingLesson::create([
            'module_id'        => $module->id,
            'title'            => $request->title,
            'description'      => $request->description,
            'video_path'       => $videoPath,
            'duration_seconds' => $request->integer('duration_seconds', 0),
            'sort_order'       => $maxOrder + 1,
            'is_published'     => false,
        ]);

        return redirect()->route('training.watch', [$module->id, $lesson->id])
            ->with('success', 'Lesson created.');
    }

    public function updateLesson(Request $request, TrainingLesson $lesson): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'video'            => ['nullable', 'file', 'mimes:mp4,mov,avi,webm,mkv,m4v', 'max:512000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'remove_video'     => ['boolean'],
        ]);

        $updateData = $request->only('title', 'description', 'duration_seconds');

        if ($request->hasFile('video')) {
            $this->deleteVideo($lesson->video_path);
            $updateData['video_path'] = $request->file('video')->store('training/videos', $this->videoDisk());
        } elseif ($request->boolean('remove_video') && $lesson->video_path) {
            $this->deleteVideo($lesson->video_path);
            $updateData['video_path'] = null;
        }

        $lesson->update($updateData);

        return back()->with('success', 'Lesson updated.');
    }

    public function toggleLesson(TrainingLesson $lesson): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $lesson->update(['is_published' => ! $lesson->is_published]);

        return back()->with('success', $lesson->is_published ? 'Lesson published.' : 'Lesson unpublished.');
    }

    public function destroyLesson(TrainingLesson $lesson): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $module = $lesson->module;
        $this->deleteVideo($lesson->video_path);
        $lesson->delete();

        $first = $module->publishedLessons()->first();
        if ($first) {
            return redirect()->route('training.watch', [$module->id, $first->id])
                ->with('success', 'Lesson deleted.');
        }

        return redirect()->route('training.index')->with('success', 'Lesson deleted.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function videoDisk(): string
    {
        return config('filesystems.disks.r2.bucket') ? 'r2' : 'public';
    }

    private function deleteVideo(?string $path): void
    {
        if ($path) {
            Storage::disk($this->videoDisk())->delete($path);
        }
    }
}
