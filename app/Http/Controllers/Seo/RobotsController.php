<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /billing',
            'Disallow: /checkout/cancel',
            'Disallow: /checkout/success',
            'Disallow: /claim-purchase',
            'Disallow: /gift-claim',
            'Disallow: /gifts',
            'Disallow: /learn',
            'Disallow: /my-courses',
            'Disallow: /profile',
            'Disallow: /receipts',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
