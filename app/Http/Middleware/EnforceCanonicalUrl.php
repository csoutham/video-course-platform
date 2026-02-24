<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceCanonicalUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('seo.enforce_canonical_host', false)) {
            return $next($request);
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        if ($request->is('api/*')) {
            return $next($request);
        }

        $preferred = parse_url((string) config('app.url'));

        $preferredHost = isset($preferred['host']) ? (string) $preferred['host'] : '';
        $preferredScheme = isset($preferred['scheme']) ? (string) $preferred['scheme'] : '';

        if ($preferredHost === '' || $preferredScheme === '') {
            return $next($request);
        }

        $currentHost = $request->getHost();
        $currentScheme = $request->getScheme();

        if ($currentHost === $preferredHost && $currentScheme === $preferredScheme) {
            return $next($request);
        }

        $target = $preferredScheme.'://'.$preferredHost.$request->getRequestUri();

        return redirect()->away($target, 301);
    }
}
