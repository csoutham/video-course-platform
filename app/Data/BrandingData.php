<?php

namespace App\Data;

class BrandingData
{
    /**
     * @param array<string, string> $colors
     * @param array<int, string> $fontPreconnectUrls
     */
    public function __construct(
        public readonly string $platformName,
        public readonly ?string $logoUrl,
        public readonly string $fontProvider,
        public readonly string $fontFamily,
        public readonly string $fontWeights,
        public readonly string $fontCssFamily,
        public readonly ?string $fontStylesheetUrl,
        public readonly array $fontPreconnectUrls,
        public readonly array $colors,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'platform_name' => $this->platformName,
            'logo_url' => $this->logoUrl,
            'font_provider' => $this->fontProvider,
            'font_family' => $this->fontFamily,
            'font_weights' => $this->fontWeights,
            'font_css_family' => $this->fontCssFamily,
            'font_stylesheet_url' => $this->fontStylesheetUrl,
            'font_preconnect_urls' => $this->fontPreconnectUrls,
            'colors' => $this->colors,
        ];
    }
}
