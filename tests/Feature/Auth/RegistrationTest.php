<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->TEST_PASSWORD = 'S3cure!Trail-4821';
});

test('registration screen can be rendered', function (): void {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');

});

test('new users can register', function (): void {
    $component = Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', $this->TEST_PASSWORD)
        ->set('password_confirmation', $this->TEST_PASSWORD);

    $component->call('register');

    $component->assertRedirect(route('my-courses.index', absolute: false));

    $this->assertAuthenticated();

});
