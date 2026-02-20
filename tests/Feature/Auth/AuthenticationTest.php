<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('login screen can be rendered', function (): void {
    $response = $this->get('/login');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.login');

});

test('users can authenticate using the login screen', function (): void {
    $user = User::factory()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password');

    $component->call('login');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('my-courses.index', absolute: false));

    $this->assertAuthenticated();

});

test('users can not authenticate with invalid password', function (): void {
    $user = User::factory()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password');

    $component->call('login');

    $component
        ->assertHasErrors()
        ->assertNoRedirect();

    $this->assertGuest();

});

test('navigation menu can be rendered', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/my-courses');

    $response
        ->assertOk()
        ->assertSee('Courses')
        ->assertSee('My Courses')
        ->assertSee('My Gifts')
        ->assertDontSee('Admin')
        ->assertSee('Profile');

});

test('admin users do not see admin links on customer navigation', function (): void {
    $user = User::factory()->admin()->create();

    $this->actingAs($user);

    $response = $this->get('/my-courses');

    $response
        ->assertOk()
        ->assertDontSee('Admin')
        ->assertDontSee('Branding');

});

test('users can logout', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('layout.navigation');

    $component->call('logout');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();

});
