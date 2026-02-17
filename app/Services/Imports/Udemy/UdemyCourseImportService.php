<?php

namespace App\Services\Imports\Udemy;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UdemyCourseImportService
{
    public function __construct(
        private readonly UdemyLandingPageFetcher $fetcher,
        private readonly UdemyStructuredDataParser $parser,
    ) {
    }

    /**
     * @return array{
     *   parsed: array<string, mixed>,
     *   existing_course: Course|null
     * }
     */
    public function preview(string $sourceUrl, ?string $sourceHtml = null): array
    {
        $html = $this->resolveSourceHtml($sourceUrl, $sourceHtml);
        $parsed = $this->parser->parse($sourceUrl, $html);
        $existing = Course::query()->firstWhere('source_url', $sourceUrl);

        return [
            'parsed' => $parsed,
            'existing_course' => $existing,
        ];
    }

    /**
     * @return array{
     *   course: Course,
     *   created_lessons: int,
     *   updated_lessons: int,
     *   mode: string
     * }
     */
    public function commit(string $sourceUrl, string $overwriteMode = 'safe_merge', ?string $sourceHtml = null): array
    {
        $html = $this->resolveSourceHtml($sourceUrl, $sourceHtml);
        $parsed = $this->parser->parse($sourceUrl, $html);

        $createdLessons = 0;
        $updatedLessons = 0;

        $course = DB::transaction(function () use (
            $sourceUrl,
            $overwriteMode,
            $parsed,
            &$createdLessons,
            &$updatedLessons,
        ): Course {
            $course = Course::query()->firstWhere('source_url', $sourceUrl);
            $isNewCourse = ! $course;

            if ($isNewCourse) {
                $course = Course::query()->create([
                    'source_platform' => 'udemy',
                    'source_url' => $sourceUrl,
                    'source_external_id' => $parsed['source_external_id'],
                    'slug' => $this->resolveCourseSlug($parsed['title']),
                    'title' => $parsed['title'],
                    'description' => $parsed['description'],
                    'thumbnail_url' => $parsed['thumbnail_url'],
                    'source_payload_json' => $parsed,
                    'source_last_imported_at' => now(),
                    'price_amount' => 0,
                    'price_currency' => 'usd',
                    'is_published' => false,
                ]);
            } else {
                $course->forceFill([
                    'source_platform' => 'udemy',
                    'source_url' => $sourceUrl,
                    'source_external_id' => $parsed['source_external_id'],
                    'source_payload_json' => $parsed,
                    'source_last_imported_at' => now(),
                ]);

                $replaceMetadata = in_array($overwriteMode, ['force_replace_metadata', 'full_replace_imported'], true);

                if ($replaceMetadata) {
                    $course->title = $parsed['title'];
                    $course->description = $parsed['description'];
                    $course->thumbnail_url = $parsed['thumbnail_url'];
                } else {
                    if (! $course->title) {
                        $course->title = $parsed['title'];
                    }
                    if (! $course->description) {
                        $course->description = $parsed['description'];
                    }
                    if (! $course->thumbnail_url) {
                        $course->thumbnail_url = $parsed['thumbnail_url'];
                    }
                }

                $course->save();
            }

            $replaceCurriculum = in_array($overwriteMode, ['force_replace_curriculum', 'full_replace_imported'], true);
            if ($replaceCurriculum) {
                CourseLesson::query()
                    ->where('course_id', $course->id)
                    ->where('is_imported_shell', true)
                    ->delete();

                CourseModule::query()
                    ->where('course_id', $course->id)
                    ->where('is_imported_shell', true)
                    ->delete();
            }

            $modulePosition = 1;
            foreach ($parsed['modules'] as $moduleData) {
                $module = CourseModule::query()->firstWhere([
                    'course_id' => $course->id,
                    'source_external_key' => $moduleData['source_key'],
                ]);

                if (! $module) {
                    $module = CourseModule::query()->create([
                        'course_id' => $course->id,
                        'title' => $moduleData['name'],
                        'sort_order' => $modulePosition,
                        'is_imported_shell' => true,
                        'source_external_key' => $moduleData['source_key'],
                    ]);
                } else {
                    $module->forceFill([
                        'title' => $moduleData['name'],
                        'sort_order' => $modulePosition,
                        'is_imported_shell' => true,
                    ])->save();
                }

                $lessonPosition = 1;
                foreach ($moduleData['lessons'] as $lessonData) {
                    $lesson = CourseLesson::query()->firstWhere([
                        'course_id' => $course->id,
                        'module_id' => $module->id,
                        'source_external_key' => $lessonData['source_key'],
                    ]);

                    if (! $lesson) {
                        CourseLesson::query()->create([
                            'course_id' => $course->id,
                            'module_id' => $module->id,
                            'title' => $lessonData['name'],
                            'slug' => $this->resolveLessonSlug($course, $lessonData['name'], $lessonPosition),
                            'summary' => null,
                            'stream_video_id' => null,
                            'duration_seconds' => $lessonData['duration_seconds'],
                            'sort_order' => $lessonPosition,
                            'is_published' => false,
                            'is_imported_shell' => true,
                            'source_external_key' => $lessonData['source_key'],
                        ]);
                        $createdLessons++;
                    } else {
                        $lesson->forceFill([
                            'title' => $lessonData['name'],
                            'duration_seconds' => $lessonData['duration_seconds'],
                            'sort_order' => $lessonPosition,
                            'is_imported_shell' => true,
                        ])->save();
                        $updatedLessons++;
                    }

                    $lessonPosition++;
                }

                $modulePosition++;
            }

            return $course->fresh(['modules.lessons']) ?? $course;
        });

        return [
            'course' => $course,
            'created_lessons' => $createdLessons,
            'updated_lessons' => $updatedLessons,
            'mode' => $overwriteMode,
        ];
    }

    private function resolveCourseSlug(string $title): string
    {
        $base = Str::of($title)->slug()->value();
        $base = $base !== '' ? $base : 'imported-course';
        $candidate = $base;
        $counter = 1;

        while (Course::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function resolveLessonSlug(Course $course, string $title, int $position): string
    {
        $base = Str::of($title)->slug()->value();
        $base = $base !== '' ? $base : 'lesson-'.$position;
        $candidate = $base;
        $counter = 1;

        while (
            CourseLesson::query()
                ->where('course_id', $course->id)
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function resolveSourceHtml(string $sourceUrl, ?string $sourceHtml = null): string
    {
        $trimmedHtml = trim((string) $sourceHtml);

        if ($trimmedHtml !== '') {
            return $trimmedHtml;
        }

        return $this->fetcher->fetch($sourceUrl);
    }
}
