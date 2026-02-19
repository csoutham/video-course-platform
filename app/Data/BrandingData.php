<?php

namespace App\Data;

class BrandingData
{
    /**
     * @param array<string, string> $colors
     */
    public function __construct(
        public readonly string $platformName,
        public readonly ?string $logoUrl,
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
            'colors' => $this->colors,
        ];
    }
}
