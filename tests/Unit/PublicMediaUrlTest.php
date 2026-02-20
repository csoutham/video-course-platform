<?php

use App\Support\PublicMediaUrl;

test('public media resolver rewrites managed thumbnail urls to configured public domain', function (): void {
    config()->set('filesystems.public_media_url', 'https://media.example.com');
    config()->set('filesystems.public_media_path_prefixes', ['course-thumbnails', 'branding']);

    $resolved = PublicMediaUrl::resolve('https://old.example.com/storage/course-thumbnails/demo.webp');

    expect($resolved)->toBe('https://media.example.com/course-thumbnails/demo.webp');
});

test('public media resolver does not rewrite unrelated external urls', function (): void {
    config()->set('filesystems.public_media_url', 'https://media.example.com');
    config()->set('filesystems.public_media_path_prefixes', ['course-thumbnails', 'branding']);

    $resolved = PublicMediaUrl::resolve('https://images.udemycdn.com/course/750x422/sample.jpg');

    expect($resolved)->toBe('https://images.udemycdn.com/course/750x422/sample.jpg');
});

test('public media url generation uses configured public domain', function (): void {
    config()->set('filesystems.public_media_url', 'https://media.example.com');

    $resolved = PublicMediaUrl::forStoragePath('branding/logo.webp', 's3');

    expect($resolved)->toBe('https://media.example.com/branding/logo.webp');
});
