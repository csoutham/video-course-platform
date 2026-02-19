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
        ]);
    }

    public function update(Request $request, BrandingService $brandingService): RedirectResponse
    {
        $validated = $request->validate([
            'platform_name' => ['required', 'string', 'max:120'],
            'logo' => ['nullable', 'file', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
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
