<?php

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sitemap includes catalog and published courses only', function (): void {
    Course::factory()->published()->create([
        'slug' => 'published-seo-course',
    ]);

    Course::factory()->unpublished()->create([
        'slug' => 'draft-seo-course',
    ]);

    $response = $this->get(route('sitemap'));

    $response
        ->assertOk()
        ->assertHeader('content-type', 'application/xml; charset=UTF-8')
        ->assertSee(route('courses.index'), false)
        ->assertSee(route('courses.show', 'published-seo-course'), false)
        ->assertDontSee(route('courses.show', 'draft-seo-course'), false);
});

test('robots endpoint advertises sitemap and disallow rules', function (): void {
    $response = $this->get(route('robots'));

    $response
        ->assertOk()
        ->assertHeader('content-type', 'text/plain; charset=UTF-8')
        ->assertSee('User-agent: *', false)
        ->assertSee('Disallow: /admin', false)
        ->assertSee('Disallow: /checkout/success', false)
        ->assertSee('Sitemap: '.route('sitemap'), false);
});

test('public course pages render index robots and canonical tags', function (): void {
    Course::factory()->published()->create([
        'title' => 'Canonical Course',
        'slug' => 'canonical-course',
    ]);

    $this->get(route('courses.show', 'canonical-course'))
        ->assertOk()
        ->assertSee('name="robots" content="index, follow, max-image-preview:large"', false)
        ->assertSee('name="googlebot" content="index, follow, max-image-preview:large"', false)
        ->assertSee('rel="canonical" href="'.route('courses.show', 'canonical-course').'"', false)
        ->assertSee('"@type":"Organization"', false)
        ->assertSee('"@type":"WebSite"', false);
});

test('checkout success page renders noindex robots meta', function (): void {
    $this->get(route('checkout.success'))
        ->assertOk()
        ->assertSee('name="robots" content="noindex, nofollow"', false)
        ->assertSee('name="googlebot" content="noindex, nofollow"', false)
        ->assertDontSee('"@type":"Organization"', false)
        ->assertDontSee('"@type":"WebSite"', false);
});

test('canonical host middleware redirects public requests when enabled', function (): void {
    config()->set('seo.enforce_canonical_host', true);
    config()->set('app.url', 'https://courses.example.com');

    $response = $this
        ->withServerVariables([
            'HTTP_HOST' => 'staging.example.test',
            'HTTPS' => 'on',
        ])
        ->get('/courses');

    $response
        ->assertRedirect('https://courses.example.com/courses')
        ->assertStatus(301);
});
