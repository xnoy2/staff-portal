<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class BcfApiService
{
    private string $base;
    private string $apiKey;

    public function __construct()
    {
        $this->base   = rtrim(config('services.bcf.base_url'), '/');
        $this->apiKey = config('services.bcf.api_key');
    }

    public function getOrders(): array
    {
        return $this->get('/orders')->json() ?? [];
    }

    public function getOrder(string|int $id): array
    {
        return $this->get("/orders/{$id}")->json() ?? [];
    }

    public function updateStage(string|int $id, string $status): Response
    {
        return $this->patch("/stages/{$id}", ['status' => $status]);
    }

    public function completeTask(string|int $id, bool $completed, ?string $notes = null): Response
    {
        $payload = ['completed' => $completed];
        if ($notes !== null) $payload['notes'] = $notes;
        return $this->patch("/tasks/{$id}", $payload);
    }

    private function get(string $path, array $query = []): Response
    {
        return Http::withHeaders(['x-api-key' => $this->apiKey])
            ->acceptJson()
            ->get($this->base . $path, $query);
    }

    private function patch(string $path, array $data = []): Response
    {
        return Http::withHeaders(['x-api-key' => $this->apiKey])
            ->acceptJson()
            ->patch($this->base . $path, $data);
    }
}
