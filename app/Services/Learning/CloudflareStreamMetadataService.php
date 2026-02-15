<?php

namespace App\Services\Learning;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudflareStreamMetadataService
{
    public function durationSeconds(string $streamVideoId): ?int
    {
        $accountId = (string) config('services.cloudflare_stream.account_id');
        $apiToken = (string) config('services.cloudflare_stream.api_token');

        throw_if($accountId === '' || $apiToken === '', RuntimeException::class, 'CF_STREAM_ACCOUNT_ID and CF_STREAM_API_TOKEN are required to sync Stream lesson durations.');

        $response = Http::withToken($apiToken)
            ->acceptJson()
            ->get('https://api.cloudflare.com/client/v4/accounts/'.$accountId.'/stream/'.$streamVideoId);

        throw_unless($response->successful(), RuntimeException::class, 'Failed to fetch Cloudflare Stream metadata for video '.$streamVideoId.'.');

        $duration = $response->json('result.duration');

        if (! is_numeric($duration)) {
            return null;
        }

        return max(0, (int) round((float) $duration));
    }
}
