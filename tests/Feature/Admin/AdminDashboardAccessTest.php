<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to login', function (): void {
    $this->get(route('admin.dashboard'))
         ->assertRedirect(route('login'));
});

test('non admin users receive forbidden', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.dashboard'))
         ->assertForbidden();
});

test('admin users can open dashboard', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.dashboard'))
         ->assertOk()
         ->assertSeeText('Recent Orders')
         ->assertSee('data-admin-shell="true"', false)
         ->assertSee('Admin Menu')
         ->assertDontSee('All rights reserved.');
});
