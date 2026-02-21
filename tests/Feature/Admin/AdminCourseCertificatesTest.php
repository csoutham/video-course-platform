<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('filesystems.image_upload_disk', 'public');
    Storage::fake('public');
});

test('admin can view course certificate settings screen', function (): void {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.courses.certificate.edit', $course))
        ->assertOk()
        ->assertSeeText('Certificate Settings');
});

test('admin can update certificate settings and upload template', function (): void {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->create([
        'certificate_enabled' => false,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.courses.certificate.update', $course), [
            'certificate_enabled' => '1',
            'certificate_signatory_name' => 'Jane Coach',
            'certificate_signatory_title' => 'Founder',
            'certificate_template_pdf' => UploadedFile::fake()->create('template.pdf', 20, 'application/pdf'),
        ])
        ->assertRedirect(route('admin.courses.certificate.edit', $course));

    $course->refresh();

    expect($course->certificate_enabled)->toBeTrue();
    expect($course->certificate_template_path)->not()->toBeNull();
    expect($course->certificate_signatory_name)->toBe('Jane Coach');
    expect($course->certificate_signatory_title)->toBe('Founder');
});

test('enabling certificates without template returns validation error', function (): void {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->create([
        'certificate_enabled' => false,
        'certificate_template_path' => null,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.courses.certificate.edit', $course))
        ->put(route('admin.courses.certificate.update', $course), [
            'certificate_enabled' => '1',
        ])
        ->assertRedirect(route('admin.courses.certificate.edit', $course))
        ->assertSessionHasErrors('certificate_template_pdf');
});
