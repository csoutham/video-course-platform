<?php

namespace App\Services\Learning;

class VideoPlaybackService
{
    public function streamEmbedUrl(string $streamVideoId): string
    {
        $baseUrl = rtrim((string) config('services.cloudflare_stream.iframe_base_url', 'https://iframe.videodelivery.net'), '/');

        return "{$baseUrl}/{$streamVideoId}";
    }
}
