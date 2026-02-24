<?php

namespace App\Support\Seo;

use Illuminate\Http\Request;

class SeoMeta
{
    public const INDEX_ROBOTS = 'index, follow, max-image-preview:large';

    public const NOINDEX_ROBOTS = 'noindex, nofollow';

    public static function canonicalUrl(?string $canonicalUrl = null): string
    {
        if ($canonicalUrl !== null && $canonicalUrl !== '') {
            return self::stripQueryAndFragment($canonicalUrl);
        }

        return self::stripQueryAndFragment(url()->current());
    }

    public static function robotsForRequest(Request $request, ?string $override = null): string
    {
        if ($override !== null && $override !== '') {
            return $override;
        }

        if ($request->routeIs([
            'admin.*',
            'billing.*',
            'certificates.verify',
            'checkout.cancel',
            'checkout.success',
            'claim-purchase.*',
            'gift-claim.*',
            'gifts.*',
            'learn.*',
            'my-courses.*',
            'profile',
            'receipts.*',
        ])) {
            return self::NOINDEX_ROBOTS;
        }

        return self::INDEX_ROBOTS;
    }

    private static function stripQueryAndFragment(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';

        return $scheme.$host.$port.$path;
    }
}
