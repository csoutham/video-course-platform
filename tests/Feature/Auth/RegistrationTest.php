<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const string TEST_PASSWORD = 'S3cure!Trail-4821';

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', self::TEST_PASSWORD)
            ->set('password_confirmation', self::TEST_PASSWORD);

        $component->call('register');

        $component->assertRedirect(route('my-courses.index', absolute: false));

        $this->assertAuthenticated();
    }
}
