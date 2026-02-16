<?php

namespace App\Services\Imports\Udemy;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class UdemyStructuredDataParser
{
    /**
     * @return array{
     *   source_url: string,
     *   source_external_id: string,
     *   title: ?string,
     *   description: ?string,
     *   thumbnail_url: ?string,
     *   provider_name: ?string,
     *   language: ?string,
     *   modules: array<int, array{
     *     name: string,
     *     duration_seconds: int|null,
     *     source_key: string,
     *     lessons: array<int, array{name: string, duration_seconds: int|null, source_key: string}>
     *   }>,
     *   warnings: array<int, string>,
     *   raw: array<string, mixed>
     * }
     */
    public function parse(string $sourceUrl, string $html): array
    {
        $warnings = [];
        $sourceExternalId = $this->extractSourceExternalId($sourceUrl);

        $structuredCandidates = $this->extractJsonLdBlocks($html);
        $courseNode = $this->selectCourseNode($structuredCandidates);

        $title = $this->stringOrNull(Arr::get($courseNode, 'name'));
        $description = $this->stringOrNull(Arr::get($courseNode, 'description'));
        $thumbnailUrl = $this->stringOrNull(Arr::get($courseNode, 'image'));
        $providerName = $this->stringOrNull(Arr::get($courseNode, 'provider.name'));
        $language = $this->stringOrNull(Arr::get($courseNode, 'inLanguage'));

        if (! $title) {
            $title = $this->extractMeta($html, 'property', 'og:title') ?? $this->extractTitleTag($html);
            $warnings[] = 'Course title pulled from fallback metadata.';
        }

        if (! $description) {
            $description = $this->extractMeta($html, 'name', 'description');
            $warnings[] = 'Course description pulled from fallback metadata.';
        }

        if (! $thumbnailUrl) {
            $thumbnailUrl = $this->extractMeta($html, 'property', 'og:image');
            $warnings[] = 'Course thumbnail pulled from fallback metadata.';
        }

        throw_unless($title, RuntimeException::class, 'Unable to parse course title from source page.');

        $modules = [];
        $rawSections = Arr::get($courseNode, 'syllabusSections', []);
        if (is_array($rawSections)) {
            $modulePosition = 1;
            foreach ($rawSections as $section) {
                $name = $this->stringOrNull(Arr::get($section, 'name'));
                if (! $name) {
                    continue;
                }

                $durationSeconds = $this->parseIsoDuration($this->stringOrNull(Arr::get($section, 'timeRequired')));
                $moduleKey = sha1($sourceUrl.'|module|'.$modulePosition.'|'.$name);

                $lessons = $this->extractLessonsFromSection($sourceUrl, $moduleKey, $section);
                if ($lessons === []) {
                    $lessons[] = [
                        'name' => $name,
                        'duration_seconds' => $durationSeconds,
                        'source_key' => sha1($sourceUrl.'|lesson-fallback|'.$modulePosition.'|'.$name),
                    ];
                }

                $modules[] = [
                    'name' => $name,
                    'duration_seconds' => $durationSeconds,
                    'source_key' => $moduleKey,
                    'lessons' => $lessons,
                ];
                $modulePosition++;
            }
        }

        if ($modules === [] || $this->hasOnlyFallbackLessons($modules)) {
            $htmlModules = $this->extractModulesFromCurriculumHtml($sourceUrl, $html);
            if ($htmlModules !== []) {
                $modules = $htmlModules;
                $warnings[] = 'Lesson-level data parsed from rendered curriculum HTML.';
            }
        }

        if ($modules === []) {
            $warnings[] = 'No syllabus sections found. Import will create metadata only.';
        } elseif ($this->hasOnlyFallbackLessons($modules)) {
            $warnings[] = 'Udemy did not expose lesson-level data. One fallback lesson per module was generated.';
        }

        return [
            'source_url' => $sourceUrl,
            'source_external_id' => $sourceExternalId,
            'title' => $title,
            'description' => $description,
            'thumbnail_url' => $thumbnailUrl,
            'provider_name' => $providerName,
            'language' => $language,
            'modules' => $modules,
            'warnings' => array_values(array_unique($warnings)),
            'raw' => [
                'course_node' => $courseNode,
            ],
        ];
    }

    private function extractSourceExternalId(string $sourceUrl): string
    {
        $path = parse_url($sourceUrl, PHP_URL_PATH);
        $path = is_string($path) ? trim($path, '/') : '';
        $parts = $path !== '' ? explode('/', $path) : [];

        if (count($parts) >= 2 && $parts[0] === 'course') {
            return (string) $parts[1];
        }

        return sha1($sourceUrl);
    }

    /**
     * @return array<int, mixed>
     */
    private function extractJsonLdBlocks(string $html): array
    {
        preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches);
        $blocks = $matches[1] ?? [];

        $decoded = [];

        foreach ($blocks as $block) {
            $json = html_entity_decode(trim($block), ENT_QUOTES | ENT_HTML5);
            if ($json === '') {
                continue;
            }

            try {
                $value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                continue;
            }

            if ($value !== null) {
                $decoded[] = $value;
            }
        }

        return $decoded;
    }

    /**
     * @param  array<int, mixed>  $candidates
     * @return array<string, mixed>
     */
    private function selectCourseNode(array $candidates): array
    {
        foreach ($candidates as $candidate) {
            if (is_array($candidate) && ($candidate['@type'] ?? null) === 'Course') {
                return $candidate;
            }

            $graph = Arr::get($candidate, '@graph');
            if (! is_array($graph)) {
                continue;
            }

            foreach ($graph as $node) {
                if (is_array($node) && ($node['@type'] ?? null) === 'Course') {
                    return $node;
                }
            }
        }

        return [];
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $clean = trim(strip_tags($value));

        return $clean === '' ? null : $clean;
    }

    private function extractTitleTag(string $html): ?string
    {
        if (! preg_match('/<title>(.*?)<\/title>/is', $html, $matches)) {
            return null;
        }

        return $this->stringOrNull(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5));
    }

    private function extractMeta(string $html, string $attribute, string $value): ?string
    {
        $pattern = '/<meta[^>]*'.$attribute.'=["\']'.preg_quote($value, '/').'["\'][^>]*content=["\'](.*?)["\'][^>]*>/is';
        if (! preg_match($pattern, $html, $matches)) {
            return null;
        }

        return $this->stringOrNull(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5));
    }

    private function parseIsoDuration(?string $duration): ?int
    {
        if (! $duration || ! Str::startsWith($duration, 'PT')) {
            return null;
        }

        if (! preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/', $duration, $matches)) {
            return null;
        }

        $hours = isset($matches[1]) ? (int) $matches[1] : 0;
        $minutes = isset($matches[2]) ? (int) $matches[2] : 0;
        $seconds = isset($matches[3]) ? (int) $matches[3] : 0;

        $total = ($hours * 3600) + ($minutes * 60) + $seconds;

        return $total > 0 ? $total : null;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<int, array{name: string, duration_seconds: int|null, source_key: string}>
     */
    private function extractLessonsFromSection(string $sourceUrl, string $moduleKey, array $section): array
    {
        $rawItems = Arr::get($section, 'hasPart', []);

        if (! is_array($rawItems) || $rawItems === []) {
            $rawItems = Arr::get($section, 'itemListElement', []);
        }

        if (! is_array($rawItems)) {
            return [];
        }

        $lessons = [];
        $position = 1;

        foreach ($rawItems as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = $this->stringOrNull(Arr::get($item, 'name'))
                ?? $this->stringOrNull(Arr::get($item, 'item.name'));
            if (! $name) {
                continue;
            }

            $durationSeconds = $this->parseIsoDuration(
                $this->stringOrNull(Arr::get($item, 'timeRequired'))
                    ?? $this->stringOrNull(Arr::get($item, 'item.timeRequired'))
            );

            $lessons[] = [
                'name' => $name,
                'duration_seconds' => $durationSeconds,
                'source_key' => sha1($sourceUrl.'|'.$moduleKey.'|'.$position.'|'.$name),
            ];
            $position++;
        }

        return $lessons;
    }

    /**
     * @param  array<int, array{name: string, duration_seconds: int|null, source_key: string, lessons: array<int, array{name: string, duration_seconds: int|null, source_key: string}>}>  $modules
     */
    private function hasOnlyFallbackLessons(array $modules): bool
    {
        foreach ($modules as $module) {
            if (count($module['lessons']) !== 1) {
                return false;
            }

            if ($module['lessons'][0]['name'] !== $module['name']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array{
     *   name: string,
     *   duration_seconds: int|null,
     *   source_key: string,
     *   lessons: array<int, array{name: string, duration_seconds: int|null, source_key: string}>
     * }>
     */
    private function extractModulesFromCurriculumHtml(string $sourceUrl, string $html): array
    {
        if (! class_exists(\DOMDocument::class)) {
            return [];
        }

        $document = new \DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if (! $loaded) {
            return [];
        }

        $xpath = new \DOMXPath($document);
        $moduleNodes = $xpath->query(
            '//*[contains(@class, "curriculum-section-module-scss-module__") and contains(@class, "__panel")]'
        );

        if (! $moduleNodes instanceof \DOMNodeList || $moduleNodes->length === 0) {
            return [];
        }

        $modules = [];
        $modulePosition = 1;

        foreach ($moduleNodes as $moduleNode) {
            $moduleName = $this->extractFirstTextByClassContains($xpath, $moduleNode, 'section-title');
            if (! $moduleName) {
                continue;
            }

            $moduleDurationSeconds = $this->parseUdemyDurationText(
                $this->extractFirstTextByClassContains($xpath, $moduleNode, 'section-content-stats')
            );
            $moduleKey = sha1($sourceUrl.'|module|'.$modulePosition.'|'.$moduleName);

            $lessonTitleNodes = $xpath->query(
                './/*[contains(@class, "course-lecture-title")]',
                $moduleNode
            );

            $lessons = [];
            $lessonPosition = 1;
            if ($lessonTitleNodes instanceof \DOMNodeList) {
                foreach ($lessonTitleNodes as $lessonTitleNode) {
                    $lessonName = $this->stringOrNull($lessonTitleNode->textContent);
                    if (! $lessonName) {
                        continue;
                    }

                    $rowNode = $this->closestNodeWithClassContains($lessonTitleNode, '__row');
                    $durationText = $rowNode
                        ? $this->extractFirstTextByClassContains($xpath, $rowNode, 'item-content-summary')
                        : null;

                    $lessons[] = [
                        'name' => $lessonName,
                        'duration_seconds' => $this->parseUdemyDurationText($durationText),
                        'source_key' => sha1($sourceUrl.'|'.$moduleKey.'|'.$lessonPosition.'|'.$lessonName),
                    ];

                    $lessonPosition++;
                }
            }

            if ($lessons === []) {
                $lessons[] = [
                    'name' => $moduleName,
                    'duration_seconds' => $moduleDurationSeconds,
                    'source_key' => sha1($sourceUrl.'|lesson-fallback-html|'.$modulePosition.'|'.$moduleName),
                ];
            }

            $modules[] = [
                'name' => $moduleName,
                'duration_seconds' => $moduleDurationSeconds,
                'source_key' => $moduleKey,
                'lessons' => $lessons,
            ];

            $modulePosition++;
        }

        return $modules;
    }

    private function extractFirstTextByClassContains(\DOMXPath $xpath, \DOMNode $context, string $needle): ?string
    {
        $nodes = $xpath->query('.//*[contains(@class, "'.$needle.'")]', $context);
        if (! $nodes instanceof \DOMNodeList || $nodes->length === 0) {
            return null;
        }

        return $this->stringOrNull($nodes->item(0)?->textContent);
    }

    private function closestNodeWithClassContains(\DOMNode $node, string $needle): ?\DOMNode
    {
        $current = $node->parentNode;

        while ($current instanceof \DOMElement) {
            $class = (string) $current->getAttribute('class');
            if ($class !== '' && str_contains($class, $needle)) {
                return $current;
            }

            $current = $current->parentNode;
        }

        return null;
    }

    private function parseUdemyDurationText(?string $value): ?int
    {
        $text = $this->stringOrNull($value);
        if (! $text) {
            return null;
        }

        if (preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?/', $text, $matches)) {
            $first = (int) $matches[1];
            $second = (int) $matches[2];
            $third = isset($matches[3]) ? (int) $matches[3] : null;

            if ($third === null) {
                return ($first * 60) + $second;
            }

            return ($first * 3600) + ($second * 60) + $third;
        }

        $hours = 0;
        $minutes = 0;
        $seconds = 0;

        if (preg_match('/(\d+)\s*h/i', $text, $matchHours)) {
            $hours = (int) $matchHours[1];
        }
        if (preg_match('/(\d+)\s*m(?:in)?/i', $text, $matchMinutes)) {
            $minutes = (int) $matchMinutes[1];
        }
        if (preg_match('/(\d+)\s*s(?:ec)?/i', $text, $matchSeconds)) {
            $seconds = (int) $matchSeconds[1];
        }

        $total = ($hours * 3600) + ($minutes * 60) + $seconds;

        return $total > 0 ? $total : null;
    }
}
