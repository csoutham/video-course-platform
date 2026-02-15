<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCoursesTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users_cannot_access_admin_courses(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.courses.index'))
            ->assertForbidden();
    }

    public function test_admin_users_can_view_courses_list(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Course::factory()->create(['title' => 'Laravel Testing Mastery']);

        $this->get(route('admin.courses.index'))
            ->assertOk()
            ->assertSeeText('Courses')
            ->assertSeeText('Laravel Testing Mastery');
    }
}
