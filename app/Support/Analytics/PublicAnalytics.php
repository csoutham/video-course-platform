<?php

namespace App\Support\Analytics;

use App\Data\BrandingData;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;

class PublicAnalytics
{
    public static function headSnippet(Request $request, ?BrandingData $branding): ?HtmlString
    {
        if (! $branding || ! self::shouldRenderForRequest($request)) {
            return null;
        }

        return match ($branding->analyticsProvider) {
            'rybbit' => self::buildRybbitSnippet($branding),
            'custom' => self::buildCustomSnippet($branding),
            default => null,
        };
    }

    public static function shouldRenderForRequest(Request $request): bool
    {
        return $request->routeIs('courses.index', 'courses.show');
    }

    private static function buildRybbitSnippet(BrandingData $branding): ?HtmlString
    {
        if (! $branding->analyticsSiteId || ! $branding->analyticsScriptUrl) {
            return null;
        }

        $scriptUrl = htmlspecialchars($branding->analyticsScriptUrl, ENT_QUOTES, 'UTF-8');
        $siteId = htmlspecialchars($branding->analyticsSiteId, ENT_QUOTES, 'UTF-8');

        return new HtmlString(
            sprintf('<script src="%s" data-site-id="%s" defer></script>', $scriptUrl, $siteId)
        );
    }

    private static function buildCustomSnippet(BrandingData $branding): ?HtmlString
    {
        $snippet = trim((string) $branding->analyticsCustomHeadSnippet);

        if ($snippet === '') {
            return null;
        }

        return new HtmlString($snippet);
    }
}
