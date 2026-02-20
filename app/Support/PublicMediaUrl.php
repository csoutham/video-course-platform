<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicMediaUrl
{
    public static function forStoragePath(string $path, string $disk): string
    {
        $cleanPath = ltrim(trim($path), '/');
        if ($cleanPath === '') {
            return '';
        }

        $baseUrl = self::publicBaseUrl();
        if ($baseUrl === null) {
            return (string) Storage::disk($disk)->url($cleanPath);
        }

        return rtrim($baseUrl, '/').'/'.$cleanPath;
    }

    public static function resolve(?string $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $baseUrl = self::publicBaseUrl();
        if ($baseUrl === null) {
            return $value;
        }

        $path = self::extractManagedPath($value);
        if ($path === null) {
            return $value;
        }

        return rtrim($baseUrl, '/').'/'.$path;
    }

    private static function extractManagedPath(string $value): ?string
    {
        $trimmed = trim($value);

        foreach (self::managedPrefixes() as $prefix) {
            if (str_starts_with($trimmed, $prefix.'/')) {
                return ltrim($trimmed, '/');
            }
        }

        $path = parse_url($trimmed, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        foreach (self::managedPrefixes() as $prefix) {
            if (str_contains($path, '/'.$prefix.'/')) {
                $suffix = Str::after($path, '/'.$prefix.'/');
                $suffix = ltrim($suffix, '/');

                return $suffix !== '' ? $prefix.'/'.$suffix : null;
            }
        }

        return null;
    }

    private static function publicBaseUrl(): ?string
    {
        $url = trim((string) config('filesystems.public_media_url', ''));

        return $url !== '' ? $url : null;
    }

    /**
     * @return array<int, string>
     */
    private static function managedPrefixes(): array
    {
        $configured = config('filesystems.public_media_path_prefixes', ['course-thumbnails', 'branding']);

        if (! is_array($configured)) {
            return ['course-thumbnails', 'branding'];
        }

        return collect($configured)
            ->filter(fn (mixed $prefix): bool => is_string($prefix) && trim($prefix) !== '')
            ->map(fn (string $prefix): string => trim($prefix, '/'))
            ->values()
            ->all();
    }
}
