<?php

namespace App\Services\Learning;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudflareStreamCatalogService
{
    /**
     * @return array<int, array{uid: string, name: string, duration_seconds: int|null}>
     */
    public function listVideos(int $perPage = 100): array
    {
        $accountId = (string) config('services.cloudflare_stream.account_id');
        $apiToken = (string) config('services.cloudflare_stream.api_token');

        throw_if($accountId === '' || $apiToken === '', RuntimeException::class, 'CF_STREAM_ACCOUNT_ID and CF_STREAM_API_TOKEN are required to list Stream videos.');

        $response = Http::withToken($apiToken)
            ->acceptJson()
            ->get('https://api.cloudflare.com/client/v4/accounts/'.$accountId.'/stream', [
                'status' => 'ready',
                'per_page' => max(1, min($perPage, 1000)),
                'order' => 'desc',
            ]);

        throw_unless($response->successful(), RuntimeException::class, 'Failed to list Cloudflare Stream videos.');

        $videos = $response->json('result');

        if (! is_array($videos)) {
            return [];
        }

        return collect($videos)
            ->map(function ($video): ?array {
                $uid = data_get($video, 'uid');

                if (! is_string($uid) || $uid === '') {
                    return null;
                }

                $duration = data_get($video, 'duration');
                $durationSeconds = is_numeric($duration) ? max(0, (int) round((float) $duration)) : null;

                return [
                    'uid' => $uid,
                    'name' => (string) (data_get($video, 'meta.name') ?: data_get($video, 'creator') ?: $uid),
                    'duration_seconds' => $durationSeconds,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
