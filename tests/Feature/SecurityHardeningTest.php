<?php

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public pages include baseline security headers', function (): void {
    $this->get(route('courses.index'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-XSS-Protection', '0');
});

test('claim endpoint is rate limited', function (): void {
    $token = 'rate-limit-token';

    for ($attempt = 1; $attempt <= 12; $attempt++) {
        $this->post(route('claim-purchase.store', ['token' => $token]))
            ->assertNotFound();
    }

    $this->post(route('claim-purchase.store', ['token' => $token]))
        ->assertStatus(429);
});

test('checkout start endpoint is rate limited', function (): void {
    $course = Course::factory()->published()->create([
        'is_free' => true,
        'free_access_mode' => 'claim_link',
    ]);

    for ($attempt = 1; $attempt <= 30; $attempt++) {
        $this->post(route('checkout.start', $course), [
            'email' => 'rate-limit@example.com',
        ])->assertRedirect();
    }

    $this->post(route('checkout.start', $course), [
        'email' => 'rate-limit@example.com',
    ])->assertStatus(429);
});
