<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('non admin users cannot access admin courses', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.courses.index'))
        ->assertForbidden();

});

test('admin users can view courses list', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    Course::factory()->create(['title' => 'Laravel Testing Mastery']);

    $this->get(route('admin.courses.index'))
        ->assertOk()
        ->assertSeeText('Courses')
        ->assertSeeText('Laravel Testing Mastery');

});
