<?php

namespace App\Services\Learning;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class VideoPlaybackService
{
    public function streamEmbedUrl(string $streamVideoId): string
    {
        if (! config('services.cloudflare_stream.signed_urls_enabled', false)) {
            $baseUrl = rtrim((string) config('services.cloudflare_stream.iframe_base_url', 'https://iframe.videodelivery.net'), '/');

            return "{$baseUrl}/{$streamVideoId}";
        }

        $token = $this->signedTokenForVideo($streamVideoId);
        $customerCode = (string) config('services.cloudflare_stream.customer_code');

        throw_if($customerCode === '', RuntimeException::class, 'CF_STREAM_CUSTOMER_CODE is required when signed Stream URLs are enabled.');

        return 'https://customer-'.$customerCode.'.cloudflarestream.com/'.$token.'/iframe';
    }

    private function signedTokenForVideo(string $streamVideoId): string
    {
        $accountId = (string) config('services.cloudflare_stream.account_id');
        $apiToken = (string) config('services.cloudflare_stream.api_token');
        $ttl = (int) config('services.cloudflare_stream.token_ttl_seconds', 3600);

        throw_if($accountId === '' || $apiToken === '', RuntimeException::class, 'CF_STREAM_ACCOUNT_ID and CF_STREAM_API_TOKEN are required when signed Stream URLs are enabled.');

        $response = Http::withToken($apiToken)
            ->acceptJson()
            ->post('https://api.cloudflare.com/client/v4/accounts/'.$accountId.'/stream/'.$streamVideoId.'/token', [
                'exp' => now()->addSeconds(max(60, $ttl))->timestamp,
            ]);

        throw_unless($response->successful(), RuntimeException::class, 'Failed to generate Cloudflare Stream signed token.');

        $token = $response->json('result.token');

        throw_if(! is_string($token) || $token === '', RuntimeException::class, 'Cloudflare Stream signed token response was invalid.');

        return $token;
    }
}
