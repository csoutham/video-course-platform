<?php

use App\Models\BrandingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('non admin users cannot access branding settings', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.branding.edit'))
        ->assertForbidden();
});

test('admin users can view branding settings screen', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.branding.edit'))
        ->assertOk()
        ->assertSeeText('Branding')
        ->assertSeeText('Core color tokens');
});

test('admin can update platform name and colors and runtime layout reflects updates', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    $this->put(route('admin.branding.update'), [
        'platform_name' => 'Acme Academy',
        'font_provider' => 'bunny',
        'font_family' => 'Figtree',
        'font_weights' => '400,500,600,700',
        'color_bg' => '#101010',
        'color_panel' => '#FFFFFF',
        'color_panel_soft' => '#F7F7F7',
        'color_border' => '#CCCCCC',
        'color_text' => '#111827',
        'color_muted' => '#6B7280',
        'color_brand' => '#0F172A',
        'color_brand_strong' => '#020617',
        'color_accent' => '#0D9488',
        'color_warning' => '#F59E0B',
    ])->assertRedirect(route('admin.branding.edit'));

    $this->assertDatabaseHas('branding_settings', [
        'platform_name' => 'Acme Academy',
        'color_bg' => '#101010',
        'color_warning' => '#F59E0B',
    ]);

    $this->get(route('courses.index'))
        ->assertOk()
        ->assertSee('Acme Academy')
        ->assertSee('--vc-bg: #101010;', false)
        ->assertSee('--vc-warning: #F59E0B;', false);
});

test('admin can upload branding logo', function (): void {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());

    $this->put(route('admin.branding.update'), [
        'platform_name' => 'Logo Test',
        'font_provider' => 'bunny',
        'font_family' => 'Figtree',
        'font_weights' => '400,500,600,700',
        'logo' => UploadedFile::fake()->image('logo.png', 320, 120),
    ])->assertRedirect(route('admin.branding.edit'));

    $branding = BrandingSetting::query()->first();
    expect($branding)->not->toBeNull();
    expect($branding?->logo_url)->not->toBeNull();
    expect($branding?->logo_url)->toContain('/storage/branding/');
});

test('invalid branding color is rejected', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    $this->from(route('admin.branding.edit'))
        ->put(route('admin.branding.update'), [
            'platform_name' => 'Acme',
            'font_provider' => 'bunny',
            'font_family' => 'Figtree',
            'font_weights' => '400,500,600,700',
            'color_bg' => '#12',
        ])
        ->assertRedirect(route('admin.branding.edit'))
        ->assertSessionHasErrors('color_bg');
});

test('google font settings are reflected in runtime layout links', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    $this->put(route('admin.branding.update'), [
        'platform_name' => 'Acme Academy',
        'font_provider' => 'google',
        'font_family' => 'Instrument Sans',
        'font_weights' => '400,500,700',
    ])->assertRedirect(route('admin.branding.edit'));

    $this->get(route('courses.index'))
        ->assertOk()
        ->assertSee('fonts.googleapis.com', false)
        ->assertSee('fonts.gstatic.com', false)
        ->assertSee('--vc-font-sans: "Instrument Sans"', false);
});

test('admin can reset branding to defaults', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    BrandingSetting::query()->create([
        'platform_name' => 'Custom Name',
        'color_bg' => '#000000',
    ]);

    $this->post(route('admin.branding.reset'))
        ->assertRedirect(route('admin.branding.edit'));

    $branding = BrandingSetting::query()->first();
    expect($branding?->platform_name)->toBe(config('branding.defaults.platform_name'));
    expect($branding?->color_bg)->toBeNull();
});
