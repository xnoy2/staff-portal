<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class BgrApiService
{
    private string $base;
    private ?string $token;

    public function __construct(?string $token = null)
    {
        $this->base  = rtrim(config('services.bgr.base_url', 'https://portal.bespokegardenroomsballycastle.co.uk'), '/');
        $this->token = $token;
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    public function login(string $email, string $password): array
    {
        $res = Http::acceptJson()
            ->post("{$this->base}/api/auth/login", compact('email', 'password'));

        if ($res->successful()) {
            return ['ok' => true, 'token' => $res->json('token'), 'user' => $res->json('user')];
        }

        return ['ok' => false, 'message' => $res->json('message', 'Login failed.')];
    }

    public function logout(): bool
    {
        return $this->post('/api/auth/logout')->successful();
    }

    public function me(): array
    {
        return $this->get('/api/auth/me')->json();
    }

    // ── Projects ──────────────────────────────────────────────────────────────

    public function getProjects(array $filters = []): array
    {
        return $this->get('/api/projects', $filters)->json();
    }

    public function getProject(int $projectId): array
    {
        return $this->get("/api/projects/{$projectId}")->json();
    }

    // ── Stages ────────────────────────────────────────────────────────────────

    public function getStages(int $projectId): array
    {
        return $this->get("/api/projects/{$projectId}/stages")->json();
    }

    public function startProject(int $projectId): array
    {
        return $this->post("/api/projects/{$projectId}/stage/start")->json();
    }

    public function completeStage(int $projectId, int $stageId): array
    {
        return $this->post("/api/projects/{$projectId}/stage/complete", ['stage_id' => $stageId])->json();
    }

    // ── Tasks (Substages) ─────────────────────────────────────────────────────

    public function addTask(int $projectId, int $stageId, string $name): array
    {
        return $this->post("/api/projects/{$projectId}/stages/{$stageId}/substages", compact('name'))->json();
    }

    public function toggleTask(int $projectId, int $stageId, int $substageId, array $data = []): array
    {
        return $this->post(
            "/api/projects/{$projectId}/stages/{$stageId}/substages/{$substageId}/toggle",
            $data
        )->json();
    }

    public function toggleTaskWithFiles(
        int $projectId,
        int $stageId,
        int $substageId,
        ?string $note = null,
        array $files = []
    ): array {
        $request = Http::acceptJson()->withToken($this->token)->asMultipart();

        if ($note !== null) {
            $request = $request->attach('note', $note);
        }

        foreach ($files as $file) {
            if (is_string($file)) {
                $request = $request->attach('photos[]', file_get_contents($file), basename($file));
            } else {
                $request = $request->attach('photos[]', $file['contents'], $file['filename']);
            }
        }

        return $request
            ->post("{$this->base}/api/projects/{$projectId}/stages/{$stageId}/substages/{$substageId}/toggle")
            ->json();
    }

    public function updateTaskNote(int $projectId, int $stageId, int $substageId, array $data): array
    {
        $request = Http::acceptJson()->withToken($this->token)->asMultipart();

        if (isset($data['note'])) {
            $request = $request->attach('note', $data['note'] ?? '');
        }

        foreach ($data['keep_photos'] ?? [] as $url) {
            $request = $request->attach('keep_photos[]', $url);
        }

        foreach ($data['new_files'] ?? [] as $file) {
            if (is_string($file)) {
                $request = $request->attach('new_photos[]', file_get_contents($file), basename($file));
            } else {
                $request = $request->attach('new_photos[]', $file['contents'], $file['filename']);
            }
        }

        return $request
            ->post("{$this->base}/api/projects/{$projectId}/stages/{$stageId}/substages/{$substageId}/note")
            ->json();
    }

    public function deleteTask(int $projectId, int $stageId, int $substageId): array
    {
        return $this->delete("/api/projects/{$projectId}/stages/{$stageId}/substages/{$substageId}")->json();
    }

    // ── Progress Updates ──────────────────────────────────────────────────────

    public function getUpdates(int $projectId, array $filters = []): array
    {
        return $this->get("/api/projects/{$projectId}/updates", $filters)->json();
    }

    public function postUpdate(int $projectId, array $data): array
    {
        return $this->post("/api/projects/{$projectId}/updates", $data)->json();
    }

    public function postUpdateWithFiles(
        int $projectId,
        string $title,
        string $body,
        ?int $stageId = null,
        array $files = []
    ): array {
        $request = Http::acceptJson()->withToken($this->token)->asMultipart()
            ->attach('title', $title)
            ->attach('body', $body);

        if ($stageId !== null) {
            $request = $request->attach('stage_id', (string) $stageId);
        }

        foreach ($files as $file) {
            if (is_string($file)) {
                $request = $request->attach('photos[]', file_get_contents($file), basename($file));
            } else {
                $request = $request->attach('photos[]', $file['contents'], $file['filename']);
            }
        }

        return $request->post("{$this->base}/api/projects/{$projectId}/updates")->json();
    }

    public function editUpdate(int $projectId, int $updateId, array $data): array
    {
        return $this->put("/api/projects/{$projectId}/updates/{$updateId}", $data)->json();
    }

    public function deleteUpdate(int $projectId, int $updateId): array
    {
        return $this->delete("/api/projects/{$projectId}/updates/{$updateId}")->json();
    }

    // ── Photo proxy ───────────────────────────────────────────────────────────

    public function fetchPhoto(string $url): Response
    {
        return Http::withToken($this->token)->get($url);
    }

    // ── Private HTTP helpers ──────────────────────────────────────────────────

    private function get(string $path, array $query = []): Response
    {
        return Http::acceptJson()
            ->withToken($this->token)
            ->get("{$this->base}{$path}", $query ?: null);
    }

    private function post(string $path, array $body = []): Response
    {
        return Http::acceptJson()
            ->withToken($this->token)
            ->post("{$this->base}{$path}", $body);
    }

    private function put(string $path, array $body = []): Response
    {
        return Http::acceptJson()
            ->withToken($this->token)
            ->put("{$this->base}{$path}", $body);
    }

    private function delete(string $path): Response
    {
        return Http::acceptJson()
            ->withToken($this->token)
            ->delete("{$this->base}{$path}");
    }
}
