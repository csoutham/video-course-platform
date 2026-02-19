<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Branding\BrandingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BrandingController extends Controller
{
    public function edit(BrandingService $brandingService): View
    {
        $branding = $brandingService->current();
        $defaults = $brandingService->defaults();

        return view('admin.branding.edit', [
            'branding' => $branding,
            'defaults' => $defaults,
            'tokenColumnMap' => $brandingService->tokenColumnMap(),
            'fontProviders' => $brandingService->supportedFontProviders(),
        ]);
    }

    public function update(Request $request, BrandingService $brandingService): RedirectResponse
    {
        $validated = $request->validate([
            'platform_name' => ['required', 'string', 'max:120'],
            'logo' => ['nullable', 'file', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'logo_height_px' => ['required', 'integer', 'min:16', 'max:120'],
            'font_provider' => ['required', 'string', 'in:system,bunny,google'],
            'font_family' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9\-\s]+$/'],
            'font_weights' => ['required', 'string', 'max:80', 'regex:/^[1-9]00(,[1-9]00)*$/'],
            'publisher_name' => ['required', 'string', 'max:120'],
            'publisher_website' => ['nullable', 'url', 'max:255'],
            'footer_tagline' => ['nullable', 'string', 'max:255'],
            'homepage_eyebrow' => ['nullable', 'string', 'max:80'],
            'homepage_title' => ['nullable', 'string', 'max:160'],
            'homepage_subtitle' => ['nullable', 'string', 'max:500'],
            'color_bg' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'color_panel' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'color_panel_soft' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'color_border' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'color_text' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'color_muted' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'color_brand' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'color_brand_strong' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'color_accent' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'color_warning' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
        ]);

        $brandingService->update(
            input: $validated,
            logo: $request->file('logo'),
        );

        return to_route('admin.branding.edit')
            ->with('status', 'Branding updated.');
    }

    public function reset(BrandingService $brandingService): RedirectResponse
    {
        $brandingService->reset();

        return to_route('admin.branding.edit')
            ->with('status', 'Branding reset to defaults.');
    }
}
