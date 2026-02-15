<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_users_receive_forbidden(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_users_can_open_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('Admin area is active.');
    }
}
