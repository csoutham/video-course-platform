<?php

namespace App\Services\Imports\Udemy;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class UdemyLandingPageFetcher
{
    public function fetch(string $sourceUrl): string
    {
        $response = Http::timeout(20)
            ->retry(2, 200, throw: false)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
            ])
            ->get($sourceUrl);

        if (! $response->successful()) {
            throw_if($response->status() === 403 && str_contains(strtolower($response->header('cf-mitigated')), 'challenge'), RuntimeException::class, 'Udemy blocked server-side fetch with Cloudflare challenge (HTTP 403). Paste page HTML from your browser into the fallback field and preview again.');

            throw new RuntimeException('Unable to fetch source URL (HTTP '.$response->status().').');
        }

        $html = (string) $response->body();

        throw_if($html === '', RuntimeException::class, 'Source page returned an empty response.');

        throw_if(str_contains($html, 'challenge-platform/scripts'), RuntimeException::class, 'Source page is protected by a challenge page. Try again later.');

        return $html;
    }
}
