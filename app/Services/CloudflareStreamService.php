<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CloudflareStreamService
{
    private string $accountId;
    private string $apiToken;
    private string $baseUrl;

    public function __construct()
    {
        $this->accountId = config('services.cloudflare.account_id', '');
        $this->apiToken  = config('services.cloudflare.stream_token', '');
        $this->baseUrl   = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/stream";
    }

    public function isConfigured(): bool
    {
        return $this->accountId !== '' && $this->apiToken !== '';
    }

    /**
     * Get a one-time direct upload URL so the browser can upload
     * directly to Cloudflare without the API token being exposed.
     * Returns ['uid' => '...', 'uploadURL' => '...']
     */
    public function getDirectUploadUrl(string $videoName = ''): array
    {
        $response = Http::withToken($this->apiToken)
            ->post("{$this->baseUrl}/direct_upload", [
                'maxDurationSeconds' => 7200,
                'requireSignedURLs'  => false,
                'meta'               => ['name' => $videoName ?: 'Training Video'],
            ]);

        $data = $response->json();

        return [
            'uid'       => $data['result']['uid']       ?? '',
            'uploadURL' => $data['result']['uploadURL'] ?? '',
        ];
    }

    /**
     * Fetch video details (status, duration, thumbnail) by UID.
     */
    public function getVideo(string $uid): array
    {
        $response = Http::withToken($this->apiToken)
            ->get("{$this->baseUrl}/{$uid}");

        return $response->json('result', []);
    }

    /**
     * Delete a video from Cloudflare Stream.
     */
    public function deleteVideo(string $uid): void
    {
        Http::withToken($this->apiToken)
            ->delete("{$this->baseUrl}/{$uid}");
    }

    /**
     * Embed URL for a given video UID.
     */
    public static function embedUrl(string $uid): string
    {
        return "https://iframe.videodelivery.net/{$uid}";
    }
}
