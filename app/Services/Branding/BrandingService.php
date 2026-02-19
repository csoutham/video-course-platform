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

    /**
     * @var array<int, string>
     */
    private array $supportedFontProviders = ['system', 'bunny', 'google'];

    public function current(): BrandingData
    {
        $defaults = $this->defaults();

        if (! $this->enabled()) {
            return $this->buildDataFromRaw($defaults, $defaults);
        }

        /** @var BrandingData $branding */
        $branding = Cache::remember(
            $this->cacheKey(),
            $this->cacheTtlSeconds(),
            function () use ($defaults): BrandingData {
                $settings = BrandingSetting::query()->first();
                if (! $settings) {
                    return $this->buildDataFromRaw($defaults, $defaults);
                }

                $raw = [
                    'platform_name' => $settings->platform_name,
                    'logo_url' => $settings->logo_url,
                    'logo_height_px' => $settings->logo_height_px,
                    'font_provider' => $settings->font_provider,
                    'font_family' => $settings->font_family,
                    'font_weights' => $settings->font_weights,
                    'publisher_name' => $settings->publisher_name,
                    'publisher_website' => $settings->publisher_website,
                    'footer_tagline' => $settings->footer_tagline,
                    'homepage_eyebrow' => $settings->homepage_eyebrow,
                    'homepage_title' => $settings->homepage_title,
                    'homepage_subtitle' => $settings->homepage_subtitle,
                    'colors' => [],
                ];

                foreach ($this->tokenToColumnMap as $token => $column) {
                    $raw['colors'][$token] = $settings->{$column};
                }

                return $this->buildDataFromRaw($raw, $defaults);
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
        $settings->logo_height_px = $this->normalizeLogoHeightPx($input['logo_height_px'] ?? $defaults['logo_height_px']);

        $fontProvider = $this->normalizeFontProvider($input['font_provider'] ?? $defaults['font_provider']);
        $fontFamily = $this->normalizeFontFamily($input['font_family'] ?? $defaults['font_family'], (string) $defaults['font_family']);
        $fontWeights = $this->normalizeFontWeights($input['font_weights'] ?? $defaults['font_weights'], (string) $defaults['font_weights']);

        $settings->font_provider = $fontProvider;
        $settings->font_family = $fontFamily;
        $settings->font_weights = $fontWeights;
        $settings->publisher_name = $this->normalizeString(
            $input['publisher_name'] ?? $defaults['publisher_name'],
            (string) $defaults['publisher_name'],
            120
        );
        $settings->publisher_website = $this->normalizeUrl($input['publisher_website'] ?? $defaults['publisher_website']);
        $settings->footer_tagline = $this->normalizeNullableString(
            $input['footer_tagline'] ?? $defaults['footer_tagline'],
            255
        );
        $settings->homepage_eyebrow = $this->normalizeNullableString(
            $input['homepage_eyebrow'] ?? $defaults['homepage_eyebrow'],
            80
        );
        $settings->homepage_title = $this->normalizeNullableString(
            $input['homepage_title'] ?? $defaults['homepage_title'],
            160
        );
        $settings->homepage_subtitle = $this->normalizeNullableString(
            $input['homepage_subtitle'] ?? $defaults['homepage_subtitle'],
            500
        );

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
        $settings->logo_height_px = $defaults['logo_height_px'];
        $settings->font_provider = $defaults['font_provider'];
        $settings->font_family = $defaults['font_family'];
        $settings->font_weights = $defaults['font_weights'];
        $settings->publisher_name = $defaults['publisher_name'];
        $settings->publisher_website = $defaults['publisher_website'];
        $settings->footer_tagline = $defaults['footer_tagline'];
        $settings->homepage_eyebrow = $defaults['homepage_eyebrow'];
        $settings->homepage_title = $defaults['homepage_title'];
        $settings->homepage_subtitle = $defaults['homepage_subtitle'];

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
     * @return array{
     *     platform_name:string,
     *     logo_url:?string,
     *     logo_height_px:int,
     *     font_provider:string,
     *     font_family:string,
     *     font_weights:string,
     *     publisher_name:string,
     *     publisher_website:?string,
     *     footer_tagline:?string,
     *     homepage_eyebrow:?string,
     *     homepage_title:?string,
     *     homepage_subtitle:?string,
     *     colors:array<string,string>
     * }
     */
    public function defaults(): array
    {
        $defaults = (array) config('branding.defaults', []);
        $platformName = (string) ($defaults['platform_name'] ?? config('app.name', 'VideoCourses'));
        $logoUrl = isset($defaults['logo_url']) && is_string($defaults['logo_url']) ? $defaults['logo_url'] : null;
        $logoHeightPx = $this->normalizeLogoHeightPx($defaults['logo_height_px'] ?? 32);
        $fontProvider = $this->normalizeFontProvider($defaults['font_provider'] ?? 'bunny');
        $fontFamily = $this->normalizeFontFamily($defaults['font_family'] ?? 'Figtree', 'Figtree');
        $fontWeights = $this->normalizeFontWeights($defaults['font_weights'] ?? '400,500,600,700', '400,500,600,700');
        $publisherName = $this->normalizeString($defaults['publisher_name'] ?? $platformName, $platformName, 120);
        $publisherWebsite = $this->normalizeUrl($defaults['publisher_website'] ?? null);
        $footerTagline = $this->normalizeNullableString($defaults['footer_tagline'] ?? null, 255);
        $homepageEyebrow = $this->normalizeNullableString($defaults['homepage_eyebrow'] ?? null, 80);
        $homepageTitle = $this->normalizeNullableString($defaults['homepage_title'] ?? null, 160);
        $homepageSubtitle = $this->normalizeNullableString($defaults['homepage_subtitle'] ?? null, 500);
        $colors = (array) ($defaults['colors'] ?? []);

        $normalizedColors = [];
        foreach ($this->tokenToColumnMap as $token => $_column) {
            $normalizedColors[$token] = $this->normalizeHex($colors[$token] ?? null) ?? '#000000';
        }

        return [
            'platform_name' => $platformName,
            'logo_url' => $logoUrl,
            'logo_height_px' => $logoHeightPx,
            'font_provider' => $fontProvider,
            'font_family' => $fontFamily,
            'font_weights' => $fontWeights,
            'publisher_name' => $publisherName,
            'publisher_website' => $publisherWebsite,
            'footer_tagline' => $footerTagline,
            'homepage_eyebrow' => $homepageEyebrow,
            'homepage_title' => $homepageTitle,
            'homepage_subtitle' => $homepageSubtitle,
            'colors' => $normalizedColors,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function tokenColumnMap(): array
    {
        return $this->tokenToColumnMap;
    }

    /**
     * @return array<int, string>
     */
    public function supportedFontProviders(): array
    {
        return $this->supportedFontProviders;
    }

    /**
     * @param array<string, mixed> $raw
     * @param array<string, mixed> $defaults
     */
    private function buildDataFromRaw(array $raw, array $defaults): BrandingData
    {
        $colors = $defaults['colors'];
        $rawColors = is_array($raw['colors'] ?? null) ? $raw['colors'] : [];

        foreach ($this->tokenToColumnMap as $token => $_column) {
            $value = $this->normalizeHex($rawColors[$token] ?? null);
            if ($value) {
                $colors[$token] = $value;
            }
        }

        $fontProvider = $this->normalizeFontProvider($raw['font_provider'] ?? $defaults['font_provider']);
        $fontFamily = $this->normalizeFontFamily($raw['font_family'] ?? $defaults['font_family'], (string) $defaults['font_family']);
        $fontWeights = $this->normalizeFontWeights($raw['font_weights'] ?? $defaults['font_weights'], (string) $defaults['font_weights']);
        $fontConfig = $this->buildFontConfig($fontProvider, $fontFamily, $fontWeights);

        return new BrandingData(
            platformName: (string) ($raw['platform_name'] ?: $defaults['platform_name']),
            logoUrl: isset($raw['logo_url']) && is_string($raw['logo_url']) && $raw['logo_url'] !== '' ? $raw['logo_url'] : $defaults['logo_url'],
            logoHeightPx: $this->normalizeLogoHeightPx($raw['logo_height_px'] ?? $defaults['logo_height_px']),
            fontProvider: $fontProvider,
            fontFamily: $fontFamily,
            fontWeights: $fontWeights,
            fontCssFamily: $fontConfig['css_family'],
            fontStylesheetUrl: $fontConfig['stylesheet_url'],
            fontPreconnectUrls: $fontConfig['preconnect_urls'],
            publisherName: $this->normalizeString(
                $raw['publisher_name'] ?? $defaults['publisher_name'],
                (string) $defaults['publisher_name'],
                120
            ),
            publisherWebsite: $this->normalizeUrl($raw['publisher_website'] ?? $defaults['publisher_website']),
            footerTagline: $this->normalizeNullableString(
                $raw['footer_tagline'] ?? $defaults['footer_tagline'],
                255
            ),
            homepageEyebrow: $this->normalizeNullableString(
                $raw['homepage_eyebrow'] ?? $defaults['homepage_eyebrow'],
                80
            ),
            homepageTitle: $this->normalizeNullableString(
                $raw['homepage_title'] ?? $defaults['homepage_title'],
                160
            ),
            homepageSubtitle: $this->normalizeNullableString(
                $raw['homepage_subtitle'] ?? $defaults['homepage_subtitle'],
                500
            ),
            colors: $colors,
        );
    }

    /**
     * @return array{css_family:string,stylesheet_url:?string,preconnect_urls:array<int,string>}
     */
    private function buildFontConfig(string $provider, string $family, string $weights): array
    {
        $systemFallback = 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif';

        if ($provider === 'system') {
            return [
                'css_family' => $systemFallback,
                'stylesheet_url' => null,
                'preconnect_urls' => [],
            ];
        }

        $cssFamily = '"'.str_replace('"', '', $family).'", '.$systemFallback;
        $weightsArray = collect(explode(',', $weights))
            ->map(fn (string $weight): string => trim($weight))
            ->filter()
            ->values()
            ->all();

        if ($provider === 'google') {
            $familyParam = str_replace(' ', '+', $family);
            $weightParam = implode(';', $weightsArray);

            return [
                'css_family' => $cssFamily,
                'stylesheet_url' => 'https://fonts.googleapis.com/css2?family='.$familyParam.':wght@'.$weightParam.'&display=swap',
                'preconnect_urls' => [
                    'https://fonts.googleapis.com',
                    'https://fonts.gstatic.com',
                ],
            ];
        }

        $familyParam = str_replace(' ', '+', $family);
        $weightParam = implode(',', $weightsArray);

        return [
            'css_family' => $cssFamily,
            'stylesheet_url' => 'https://fonts.bunny.net/css?family='.$familyParam.':'.$weightParam.'&display=swap',
            'preconnect_urls' => ['https://fonts.bunny.net'],
        ];
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

    private function normalizeFontProvider(mixed $value): string
    {
        $provider = Str::lower(trim((string) $value));

        if (! in_array($provider, $this->supportedFontProviders, true)) {
            return 'bunny';
        }

        return $provider;
    }

    private function normalizeFontFamily(mixed $value, string $fallback): string
    {
        $family = trim((string) $value);
        $family = preg_replace('/[^A-Za-z0-9\-\s]/', '', $family) ?: '';
        $family = Str::of($family)->squish()->value();

        if ($family === '') {
            return $fallback;
        }

        return Str::limit($family, 120, '');
    }

    private function normalizeFontWeights(mixed $value, string $fallback): string
    {
        $weights = collect(explode(',', (string) $value))
            ->map(fn (string $weight): string => trim($weight))
            ->filter(fn (string $weight): bool => preg_match('/^[1-9]00$/', $weight) === 1)
            ->map(fn (string $weight): int => (int) $weight)
            ->filter(fn (int $weight): bool => $weight >= 100 && $weight <= 900)
            ->unique()
            ->sort()
            ->values();

        if ($weights->isEmpty()) {
            return $fallback;
        }

        return $weights->implode(',');
    }

    private function normalizeLogoHeightPx(mixed $value): int
    {
        $normalized = (int) $value;

        return max(16, min(120, $normalized > 0 ? $normalized : 32));
    }

    private function normalizeString(mixed $value, string $fallback, int $maxLength): string
    {
        $string = trim((string) $value);
        $string = Str::of($string)->squish()->value();

        if ($string === '') {
            return Str::limit(trim($fallback), $maxLength, '');
        }

        return Str::limit($string, $maxLength, '');
    }

    private function normalizeNullableString(mixed $value, int $maxLength): ?string
    {
        $string = trim((string) $value);
        $string = Str::of($string)->squish()->value();

        if ($string === '') {
            return null;
        }

        return Str::limit($string, $maxLength, '');
    }

    private function normalizeUrl(mixed $value): ?string
    {
        $url = trim((string) $value);

        if ($url === '') {
            return null;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return Str::limit($url, 255, '');
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
