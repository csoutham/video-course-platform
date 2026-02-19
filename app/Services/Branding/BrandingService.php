<?php

namespace App\Services\Branding;

use App\Data\BrandingData;
use App\Models\BrandingSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandingService
{
    /**
     * @var array<string, string>
     */
    private array $tokenToColumnMap = [
        'vc-bg' => 'color_bg',
        'vc-panel' => 'color_panel',
        'vc-panel-soft' => 'color_panel_soft',
        'vc-border' => 'color_border',
        'vc-text' => 'color_text',
        'vc-muted' => 'color_muted',
        'vc-brand' => 'color_brand',
        'vc-brand-strong' => 'color_brand_strong',
        'vc-accent' => 'color_accent',
        'vc-warning' => 'color_warning',
    ];

    public function current(): BrandingData
    {
        $defaults = $this->defaults();

        if (! $this->enabled()) {
            return new BrandingData(
                platformName: $defaults['platform_name'],
                logoUrl: $defaults['logo_url'],
                colors: $defaults['colors'],
            );
        }

        /** @var BrandingData $branding */
        $branding = Cache::remember(
            $this->cacheKey(),
            $this->cacheTtlSeconds(),
            function () use ($defaults): BrandingData {
                $settings = BrandingSetting::query()->first();
                if (! $settings) {
                    return new BrandingData(
                        platformName: $defaults['platform_name'],
                        logoUrl: $defaults['logo_url'],
                        colors: $defaults['colors'],
                    );
                }

                $colors = $defaults['colors'];
                foreach ($this->tokenToColumnMap as $token => $column) {
                    $value = $this->normalizeHex($settings->{$column});
                    if ($value) {
                        $colors[$token] = $value;
                    }
                }

                return new BrandingData(
                    platformName: $settings->platform_name ?: $defaults['platform_name'],
                    logoUrl: $settings->logo_url ?: $defaults['logo_url'],
                    colors: $colors,
                );
            }
        );

        return $branding;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(array $input, ?UploadedFile $logo = null): void
    {
        $defaults = $this->defaults();
        $settings = BrandingSetting::query()->firstOrNew(['id' => 1]);

        $platformName = trim((string) ($input['platform_name'] ?? $defaults['platform_name']));
        $settings->platform_name = $platformName !== '' ? $platformName : $defaults['platform_name'];

        foreach ($this->tokenToColumnMap as $column) {
            $raw = $input[$column] ?? null;
            $settings->{$column} = $this->normalizeHex($raw);
        }

        if ($logo) {
            $settings->logo_url = $this->storeLogo($logo, $settings->logo_url);
        }

        $settings->save();
        $this->flushCache();
    }

    public function reset(): void
    {
        $defaults = $this->defaults();
        $settings = BrandingSetting::query()->firstOrNew(['id' => 1]);

        $this->deleteStoredLogo($settings->logo_url);

        $settings->platform_name = $defaults['platform_name'];
        $settings->logo_url = null;

        foreach ($this->tokenToColumnMap as $column) {
            $settings->{$column} = null;
        }

        $settings->save();
        $this->flushCache();
    }

    public function flushCache(): void
    {
        Cache::forget($this->cacheKey());
    }

    /**
     * @return array{platform_name:string,logo_url:?string,colors:array<string,string>}
     */
    public function defaults(): array
    {
        $defaults = (array) config('branding.defaults', []);
        $platformName = (string) ($defaults['platform_name'] ?? config('app.name', 'VideoCourses'));
        $logoUrl = isset($defaults['logo_url']) && is_string($defaults['logo_url']) ? $defaults['logo_url'] : null;
        $colors = (array) ($defaults['colors'] ?? []);

        return [
            'platform_name' => $platformName,
            'logo_url' => $logoUrl,
            'colors' => array_map(
                fn ($value): string => $this->normalizeHex($value) ?? '#000000',
                $colors
            ),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function tokenColumnMap(): array
    {
        return $this->tokenToColumnMap;
    }

    private function enabled(): bool
    {
        return (bool) config('branding.enabled', true);
    }

    private function cacheKey(): string
    {
        return (string) config('branding.cache_key', 'branding:current');
    }

    private function cacheTtlSeconds(): int
    {
        return max(60, (int) config('branding.cache_ttl_seconds', 3600));
    }

    private function normalizeHex(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = strtoupper(trim($value));
        if (! str_starts_with($normalized, '#')) {
            $normalized = '#'.$normalized;
        }

        if (! preg_match('/^#([A-F0-9]{6})$/', $normalized)) {
            return null;
        }

        return $normalized;
    }

    private function storeLogo(UploadedFile $file, ?string $existingLogoUrl = null): ?string
    {
        $disk = (string) config('branding.disk', 'public');
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: 'png'));
        $filename = 'branding-logo-'.Str::uuid().'.'.$extension;
        $path = $file->storeAs('branding', $filename, $disk);

        if (! $path) {
            return $existingLogoUrl;
        }

        $logoUrl = Storage::disk($disk)->url($path);
        $this->deleteStoredLogo($existingLogoUrl);

        return $logoUrl;
    }

    private function deleteStoredLogo(?string $logoUrl): void
    {
        if (! $logoUrl) {
            return;
        }

        $disk = (string) config('branding.disk', 'public');
        $prefix = rtrim((string) Storage::disk($disk)->url(''), '/').'/';

        if (! str_starts_with($logoUrl, $prefix)) {
            return;
        }

        $oldPath = ltrim(Str::after($logoUrl, $prefix), '/');
        if ($oldPath !== '') {
            Storage::disk($disk)->delete($oldPath);
        }
    }
}
