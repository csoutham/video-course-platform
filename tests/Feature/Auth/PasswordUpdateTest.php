<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->NEW_PASSWORD = 'S3cure!Trail-4821';
});

test('password can be updated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-password-form')
        ->set('current_password', 'password')
        ->set('password', $this->NEW_PASSWORD)
        ->set('password_confirmation', $this->NEW_PASSWORD)
        ->call('updatePassword');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $this->assertTrue(Hash::check($this->NEW_PASSWORD, $user->refresh()->password));

});

test('correct password must be provided to update password', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-password-form')
        ->set('current_password', 'wrong-password')
        ->set('password', $this->NEW_PASSWORD)
        ->set('password_confirmation', $this->NEW_PASSWORD)
        ->call('updatePassword');

    $component
        ->assertHasErrors(['current_password'])
        ->assertNoRedirect();

});
