<?php

namespace App\Services\Marketing;

use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KitAudienceService
{
    public function syncPurchaser(Order $order): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $email = trim((string) $order->email);
        if ($email === '') {
            return;
        }

        $apiKey = (string) config('services.kit.api_key');
        if ($apiKey === '') {
            return;
        }

        try {
            $firstName = $this->resolveFirstName($order);

            $subscriberResponse = Http::baseUrl((string) config('services.kit.base_url'))
                ->acceptJson()
                ->withHeaders([
                    'X-Kit-Api-Key' => $apiKey,
                ])
                ->timeout(10)
                ->post('/subscribers', [
                    'email_address' => $email,
                    'first_name' => $firstName,
                ]);

            $subscriberResponse->throw();

            $tagIds = $this->resolveTagIds($order);

            foreach ($tagIds as $tagId) {
                Http::baseUrl((string) config('services.kit.base_url'))
                    ->acceptJson()
                    ->withHeaders([
                        'X-Kit-Api-Key' => $apiKey,
                    ])
                    ->timeout(10)
                    ->post('/tags/'.$tagId.'/subscribers', [
                        'email_address' => $email,
                    ])
                    ->throw();
            }
        } catch (\Throwable $exception) {
            Log::warning('kit_sync_failed', [
                'order_id' => $order->id,
                'email' => $order->email,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function isEnabled(): bool
    {
        return (bool) config('services.kit.enabled');
    }

    private function resolveFirstName(Order $order): ?string
    {
        $name = trim((string) ($order->user?->name ?? ''));

        if ($name === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $firstName = Arr::first($parts);

        return is_string($firstName) && $firstName !== '' ? $firstName : null;
    }

    /**
     * @return array<int>
     */
    private function resolveTagIds(Order $order): array
    {
        $globalTagIds = $this->parseTagIdList((string) config('services.kit.purchaser_tag_ids'));
        $courseTagMap = $this->parseCourseTagMap((string) config('services.kit.course_tag_map'));

        $courseTagIds = [];
        foreach ($order->items as $item) {
            $courseIdKey = 'course:'.$item->course_id;
            $courseSlugKey = $item->course?->slug ?? null;

            if (isset($courseTagMap[$courseIdKey])) {
                $courseTagIds[] = $courseTagMap[$courseIdKey];
            }

            if ($courseSlugKey && isset($courseTagMap[$courseSlugKey])) {
                $courseTagIds[] = $courseTagMap[$courseSlugKey];
            }

            if ($item->course?->kit_tag_id) {
                $courseTagIds[] = (int) $item->course->kit_tag_id;
            }
        }

        return array_values(array_unique(array_filter(
            [...$globalTagIds, ...$courseTagIds],
            fn ($tagId): bool => is_int($tagId) && $tagId > 0
        )));
    }

    /**
     * @return array<int>
     */
    private function parseTagIdList(string $raw): array
    {
        $parts = array_map('trim', explode(',', $raw));
        $ids = [];

        foreach ($parts as $part) {
            if ($part === '' || ! ctype_digit($part)) {
                continue;
            }

            $ids[] = (int) $part;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<string, int>
     */
    private function parseCourseTagMap(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $map = [];
        foreach ($decoded as $key => $value) {
            if (! is_string($key) || $key === '' || ! is_numeric($value)) {
                continue;
            }

            $tagId = (int) $value;
            if ($tagId > 0) {
                $map[$key] = $tagId;
            }
        }

        return $map;
    }
}
