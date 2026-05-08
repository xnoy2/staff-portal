<?php

namespace App\Http\Controllers;

use App\Services\BgrApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class BgrController extends Controller
{
    // ── Connect / Disconnect ──────────────────────────────────────────────────

    public function connect(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $service = new BgrApiService();
        $result  = $service->login($request->email, $request->password);

        if (! $result['ok']) {
            return back()->withErrors(['bgr_password' => $result['message']])->withInput();
        }

        auth()->user()->update(['bgr_token' => $result['token']]);

        return redirect()->route('bgr.index')->with('success', 'BGR account connected.');
    }

    public function disconnect(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->bgr_token) {
            (new BgrApiService($user->bgr_token))->logout();
            $user->update(['bgr_token' => null]);
        }

        return redirect()->route('bgr.index')->with('success', 'BGR account disconnected.');
    }

    // ── Projects list ─────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $user      = auth()->user();
        $connected = (bool) $user->bgr_token;
        $projects  = [];
        $meta      = null;
        $error     = null;

        if ($connected) {
            $service  = new BgrApiService($user->bgr_token);
            $filters  = array_filter([
                'status' => $request->get('status'),
                'search' => $request->get('search'),
                'page'   => $request->get('page'),
            ]);

            $response = $service->getProjects($filters);

            if (isset($response['data'])) {
                $projects = $response['data'];
                $meta     = $response['meta'] ?? null;
            } else {
                $error = $response['message'] ?? 'Failed to load projects.';
                if (str_contains($error, '401') || str_contains(strtolower($error), 'unauthenticated')) {
                    $user->update(['bgr_token' => null]);
                    $connected = false;
                    $error     = null;
                }
            }
        }

        return Inertia::render('Bgr/Projects', [
            'connected' => $connected,
            'projects'  => $projects,
            'meta'      => $meta,
            'filters'   => [
                'status' => $request->get('status', ''),
                'search' => $request->get('search', ''),
            ],
            'error'     => $error,
        ]);
    }

    // ── Project detail ────────────────────────────────────────────────────────

    public function show(int $id): Response|RedirectResponse
    {
        $user    = auth()->user();
        $this->requireConnected($user);

        $service  = new BgrApiService($user->bgr_token);
        $response = $service->getProject($id);

        if (! isset($response['data'])) {
            return redirect()->route('bgr.index')
                ->with('error', $response['message'] ?? 'Project not found.');
        }

        $updatesResponse = $service->getUpdates($id);

        return Inertia::render('Bgr/Project', [
            'project' => $response['data'],
            'updates' => $updatesResponse['data'] ?? [],
            'updatesMeta' => $updatesResponse['meta'] ?? null,
        ]);
    }

    // ── Task toggle ───────────────────────────────────────────────────────────

    public function toggleTask(Request $request, int $projectId, int $stageId, int $substageId): RedirectResponse
    {
        $user = auth()->user();
        $this->requireConnected($user);

        $request->validate([
            'note'      => ['nullable', 'string', 'max:5000'],
            'photos.*'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,pdf,doc,docx,xls,xlsx', 'max:20480'],
        ]);

        $service = new BgrApiService($user->bgr_token);

        if ($request->hasFile('photos')) {
            $files = [];
            foreach ($request->file('photos') as $file) {
                $files[] = [
                    'contents' => file_get_contents($file->getRealPath()),
                    'filename' => $file->getClientOriginalName(),
                ];
            }
            $service->toggleTaskWithFiles($projectId, $stageId, $substageId, $request->input('note'), $files);
        } else {
            $data = $request->input('note') !== null ? ['note' => $request->input('note')] : [];
            $service->toggleTask($projectId, $stageId, $substageId, $data);
        }

        return back()->with('success', 'Task updated.');
    }

    // ── Task note update ──────────────────────────────────────────────────────

    public function updateTaskNote(Request $request, int $projectId, int $stageId, int $substageId): RedirectResponse
    {
        $user = auth()->user();
        $this->requireConnected($user);

        $request->validate([
            'note'         => ['nullable', 'string', 'max:5000'],
            'keep_photos'  => ['nullable', 'array'],
            'keep_photos.*'=> ['string', 'url'],
            'new_photos.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,pdf,doc,docx,xls,xlsx', 'max:20480'],
        ]);

        $newFiles = [];
        foreach ($request->file('new_photos', []) as $file) {
            $newFiles[] = [
                'contents' => file_get_contents($file->getRealPath()),
                'filename' => $file->getClientOriginalName(),
            ];
        }

        (new BgrApiService($user->bgr_token))->updateTaskNote($projectId, $stageId, $substageId, [
            'note'        => $request->input('note'),
            'keep_photos' => $request->input('keep_photos', []),
            'new_files'   => $newFiles,
        ]);

        return back()->with('success', 'Task note updated.');
    }

    // ── Progress updates ──────────────────────────────────────────────────────

    public function storeUpdate(Request $request, int $projectId): RedirectResponse
    {
        $user = auth()->user();
        $this->requireConnected($user);

        $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'body'      => ['required', 'string', 'max:5000'],
            'stage_id'  => ['nullable', 'integer'],
            'photos.*'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic', 'max:10240'],
        ]);

        $service = new BgrApiService($user->bgr_token);

        if ($request->hasFile('photos')) {
            $files = [];
            foreach ($request->file('photos') as $file) {
                $files[] = [
                    'contents' => file_get_contents($file->getRealPath()),
                    'filename' => $file->getClientOriginalName(),
                ];
            }
            $service->postUpdateWithFiles(
                $projectId,
                $request->title,
                $request->body,
                $request->integer('stage_id') ?: null,
                $files
            );
        } else {
            $service->postUpdate($projectId, [
                'title'    => $request->title,
                'body'     => $request->body,
                'stage_id' => $request->integer('stage_id') ?: null,
            ]);
        }

        return back()->with('success', 'Progress update posted.');
    }

    // ── Photo proxy ───────────────────────────────────────────────────────────

    public function photo(Request $request): HttpResponse
    {
        $user = auth()->user();
        $this->requireConnected($user);

        $url = $request->query('url');

        $base = rtrim(config('services.bgr.base_url'), '/');
        abort_unless($url && str_starts_with($url, $base), 403);

        $response = (new BgrApiService($user->bgr_token))->fetchPhoto($url);

        abort_unless($response->successful(), 404);

        return response(
            $response->body(),
            200,
            ['Content-Type' => $response->header('Content-Type') ?? 'image/jpeg']
        );
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function requireConnected($user): void
    {
        abort_unless($user->bgr_token, 403, 'BGR account not connected.');
    }
}
