<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Services\BgrApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
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

        // Also capture a web session cookie so our photo proxy can bypass
        // the BGR portal's session-based photo auth (Bearer tokens don't work there).
        $session = $this->captureBgrWebSession($request->email, $request->password);

        auth()->user()->update([
            'bgr_token'   => $result['token'],
            'bgr_session' => $session,
        ]);

        $message = $session
            ? 'BGR account connected. Photos will load correctly.'
            : 'BGR account connected, but photo session could not be established — photos may not display. Try disconnecting and reconnecting.';

        return redirect()->route('bgr.index')->with('success', $message);
    }

    public function disconnect(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->bgr_token) {
            (new BgrApiService($user->bgr_token))->logout();
            $user->update(['bgr_token' => null, 'bgr_session' => null]);
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

        // Attach linked jobs from our DB to each stage
        $project  = $response['data'];
        $stageIds = collect($project['stages'] ?? [])
            ->pluck('id')
            ->map(fn ($i) => (string) $i)
            ->toArray();

        $linkedJobs = Job::whereIn('bgr_stage_id', $stageIds)
            ->with('staff:id,name,avatar')
            ->orderBy('date')
            ->get()
            ->groupBy('bgr_stage_id');

        $project['stages'] = array_map(function ($stage) use ($linkedJobs) {
            $stage['linked_jobs'] = $linkedJobs->get((string) $stage['id'], collect())
                ->map(fn ($j) => [
                    'id'         => $j->id,
                    'title'      => $j->title,
                    'date'       => $j->date->toDateString(),
                    'status'     => $j->status,
                    'start_time' => $j->start_time,
                    'staff'      => $j->staff->map(fn ($u) => [
                        'id'   => $u->id,
                        'name' => $u->name,
                    ]),
                ])
                ->values()
                ->all();
            return $stage;
        }, $project['stages'] ?? []);

        return Inertia::render('Bgr/Project', [
            'project'     => $project,
            'updates'     => $updatesResponse['data'] ?? [],
            'updatesMeta' => $updatesResponse['meta'] ?? null,
        ]);
    }

    // ── Stages proxy (used by job form dropdown) ──────────────────────────────

    public function stagesForProject(int $id): JsonResponse
    {
        $user = auth()->user();
        $this->requireConnected($user);

        $response = (new BgrApiService($user->bgr_token))->getProject($id);
        $stages   = $response['data']['stages'] ?? [];

        return response()->json(
            collect($stages)->map(fn ($s) => [
                'id'        => $s['id'],
                'name'      => $s['name'],
                'status'    => $s['status'] ?? 'pending',
                'substages' => collect($s['substages'] ?? [])->map(fn ($sub) => [
                    'id'     => $sub['id'],
                    'name'   => $sub['name'],
                    'status' => $sub['status'] ?? 'pending',
                ])->values()->all(),
            ])->values()
        );
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

    public function photo(Request $request)
    {
        $user = auth()->user();
        $this->requireConnected($user);

        $url = $request->query('url');
        abort_unless($url && str_starts_with($url, 'https://'), 403);

        try {
            // Use web session cookie — BGR photo routes use session auth, not Bearer tokens.
            // Fall back to Bearer if no session is stored.
            $headers = ['Accept' => 'image/*,*/*'];
            if ($user->bgr_session) {
                $headers['Cookie'] = $user->bgr_session;
            } else {
                $headers['Authorization'] = 'Bearer ' . $user->bgr_token;
            }

            $bgrResponse = Http::withHeaders($headers)->timeout(20)->get($url);

            if (! $bgrResponse->successful()) {
                abort(404);
            }

            $contentType = $bgrResponse->header('Content-Type') ?? 'image/jpeg';
            $contentType = trim(explode(';', $contentType)[0]) ?: 'image/jpeg';

            // BGR returned an HTML page — session has expired or is invalid.
            if (str_starts_with($contentType, 'text/')) {
                abort(404);
            }

            $body = $bgrResponse->body();

            return response()->stream(function () use ($body) {
                echo $body;
            }, 200, [
                'Content-Type'   => $contentType,
                'Content-Length' => strlen($body),
                'Cache-Control'  => 'private, max-age=3600',
            ]);
        } catch (\Throwable) {
            abort(502);
        }
    }

    // ── Debug (dev only) ──────────────────────────────────────────────────────

    public function debugSession(Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless(app()->isLocal() || $request->has('_dev'), 403);

        $request->validate(['email' => 'required|email', 'password' => 'required|string']);

        $base   = rtrim(config('services.bgr.base_url'), '/');
        $log    = [];
        $result = null;

        try {
            $jar    = new \GuzzleHttp\Cookie\CookieJar();
            $client = new \GuzzleHttp\Client([
                'cookies'         => $jar,
                'allow_redirects' => ['max' => 10, 'strict' => false, 'referer' => true, 'track_redirects' => true],
                'verify'          => false,
                'timeout'         => 30,
                'headers'         => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ],
            ]);

            $loginPage = $client->get($base . '/login');
            $loginHtml = (string) $loginPage->getBody();
            $log[]     = ['step' => 'GET /login', 'status' => $loginPage->getStatusCode(), 'cookies' => array_column($jar->toArray(), 'Name')];

            $csrfToken = null;
            if (preg_match('/<input[^>]+name=["\']_token["\'][^>]+value=["\']([^"\']+)["\']/', $loginHtml, $m) ||
                preg_match('/value=["\']([^"\']+)["\'][^>]+name=["\']_token["\']/', $loginHtml, $m)) {
                $csrfToken = $m[1];
                $log[]     = ['step' => 'CSRF from HTML', 'token_length' => strlen($csrfToken)];
            } elseif ($xsrf = $jar->getCookieByName('XSRF-TOKEN')) {
                $csrfToken = urldecode($xsrf->getValue());
                $log[]     = ['step' => 'CSRF from cookie', 'token_length' => strlen($csrfToken)];
            } else {
                $log[] = ['step' => 'CSRF', 'error' => 'not found'];
                return response()->json(['log' => $log, 'html_snippet' => substr($loginHtml, 0, 2000)]);
            }

            $postResp = $client->post($base . '/login', [
                'form_params' => ['_token' => $csrfToken, 'email' => $request->email, 'password' => $request->password],
                'headers'     => ['Referer' => $base . '/login', 'Origin' => $base],
            ]);

            $allCookies = $jar->toArray();
            $log[]      = ['step' => 'POST /login', 'status' => $postResp->getStatusCode(), 'all_cookies' => $allCookies];

            $sessionCookies = array_values(array_filter($allCookies, fn ($c) => $c['Name'] !== 'XSRF-TOKEN'));
            $result         = ! empty($sessionCookies)
                ? implode('; ', array_map(fn ($c) => $c['Name'] . '=' . substr($c['Value'], 0, 20) . '...', $sessionCookies))
                : null;

            $log[] = ['step' => 'result', 'session_cookie' => $result ?? 'NONE'];
        } catch (\Throwable $e) {
            $log[] = ['step' => 'exception', 'message' => $e->getMessage(), 'class' => get_class($e)];
        }

        return response()->json(['log' => $log, 'captured' => $result !== null]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function requireConnected($user): void
    {
        abort_unless($user->bgr_token, 403, 'BGR account not connected.');
    }

    /**
     * Log into the BGR web interface and capture the authenticated session cookie.
     * BGR photo routes use session auth (not Bearer tokens), so we need this cookie
     * to proxy photos server-side. BGR photos are served as 302 redirects to
     * pre-signed Cloudflare R2 URLs — the session cookie unlocks that redirect.
     */
    private function captureBgrWebSession(string $email, string $password): ?string
    {
        $base = rtrim(config('services.bgr.base_url'), '/');

        try {
            $jar    = new \GuzzleHttp\Cookie\CookieJar();
            $client = new \GuzzleHttp\Client([
                'cookies'         => $jar,
                'allow_redirects' => ['max' => 10, 'strict' => false, 'referer' => true, 'track_redirects' => true],
                'verify'          => false,
                'timeout'         => 30,
                'headers'         => [
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Connection'      => 'keep-alive',
                ],
            ]);

            // Step 1: Load login page — sets XSRF-TOKEN + initial session cookie
            $loginPageResponse = $client->get($base . '/login');
            $loginHtml         = (string) $loginPageResponse->getBody();

            \Log::channel('stderr')->info('BGR session: GET /login status=' . $loginPageResponse->getStatusCode());
            \Log::channel('stderr')->info('BGR session: cookies after GET=' . json_encode(array_column($jar->toArray(), 'Name')));

            // Primary: extract _token from the HTML form (most reliable)
            $csrfToken = null;
            if (preg_match('/<input[^>]+name=["\']_token["\'][^>]+value=["\']([^"\']+)["\']/', $loginHtml, $m) ||
                preg_match('/value=["\']([^"\']+)["\'][^>]+name=["\']_token["\']/', $loginHtml, $m)) {
                $csrfToken = $m[1];
                \Log::channel('stderr')->info('BGR session: CSRF token extracted from HTML, length=' . strlen($csrfToken));
            }

            // Fallback: decode XSRF-TOKEN cookie
            if (! $csrfToken) {
                $xsrfCookie = $jar->getCookieByName('XSRF-TOKEN');
                if ($xsrfCookie) {
                    $csrfToken = urldecode($xsrfCookie->getValue());
                    \Log::channel('stderr')->info('BGR session: CSRF token from cookie, length=' . strlen($csrfToken));
                }
            }

            if (! $csrfToken) {
                \Log::channel('stderr')->warning('BGR session: No CSRF token found');
                return null;
            }

            // Step 2: Submit credentials — BGR responds with 302 → authenticated session
            $postResponse = $client->post($base . '/login', [
                'form_params' => [
                    '_token'   => $csrfToken,
                    'email'    => $email,
                    'password' => $password,
                ],
                'headers' => [
                    'Referer' => $base . '/login',
                    'Origin'  => $base,
                ],
            ]);

            \Log::channel('stderr')->info('BGR session: POST /login final status=' . $postResponse->getStatusCode());
            \Log::channel('stderr')->info('BGR session: cookies after POST=' . json_encode(array_column($jar->toArray(), 'Name')));

            // Step 3: Extract the authenticated session cookie.
            // Prefer cookies whose name contains 'session'; fall back to any non-XSRF cookie.
            $allCookies     = $jar->toArray();
            $sessionCookies = array_values(array_filter(
                $allCookies,
                fn ($c) => $c['Name'] !== 'XSRF-TOKEN' && str_contains(strtolower($c['Name']), 'session')
            ));

            if (empty($sessionCookies)) {
                // Fallback: any non-XSRF cookie
                $sessionCookies = array_values(array_filter($allCookies, fn ($c) => $c['Name'] !== 'XSRF-TOKEN'));
            }

            if (empty($sessionCookies)) {
                \Log::channel('stderr')->warning('BGR session: No session cookie found in jar. All cookies: ' . json_encode($allCookies));
                return null;
            }

            $cookie = $sessionCookies[0];
            \Log::channel('stderr')->info('BGR session: captured cookie=' . $cookie['Name']);

            // Return ALL non-XSRF cookies as a header string (catches multi-cookie setups)
            $cookieHeader = implode('; ', array_map(
                fn ($c) => $c['Name'] . '=' . $c['Value'],
                $sessionCookies
            ));

            return $cookieHeader;
        } catch (\Throwable $e) {
            \Log::channel('stderr')->error('BGR session capture exception: ' . $e->getMessage());
            return null;
        }
    }
}
