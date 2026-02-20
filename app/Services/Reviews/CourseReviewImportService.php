<?php

namespace App\Services\Reviews;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;
use Carbon\CarbonImmutable;

class CourseReviewImportService
{
    /**
     * @return array{
     *   rows: array<int, array{
     *     line:int,
     *     rating:int,
     *     reviewer_name:string,
     *     title:?string,
     *     body:?string,
     *     original_reviewed_at:?string
     *   }>,
     *   errors: array<int, string>
     * }
     */
    public function previewFromText(string $sourceText): array
    {
        $lines = preg_split('/\R/u', $sourceText) ?: [];
        $rows = [];
        $errors = [];

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $parsedFields = $this->splitLine($trimmed);
            if (count($parsedFields) < 2) {
                $errors[] = "Line {$lineNumber}: expected at least rating and reviewer name.";
                continue;
            }

            $ratingRaw = trim((string) ($parsedFields[0] ?? ''));
            $reviewerName = trim((string) ($parsedFields[1] ?? ''));
            $title = $this->nullableTrimmed($parsedFields[2] ?? null);
            $body = $this->nullableTrimmed($parsedFields[3] ?? null);
            $dateRaw = $this->nullableTrimmed($parsedFields[4] ?? null);

            if (! ctype_digit($ratingRaw)) {
                $errors[] = "Line {$lineNumber}: rating must be an integer from 1 to 5.";
                continue;
            }

            $rating = (int) $ratingRaw;
            if ($rating < 1 || $rating > 5) {
                $errors[] = "Line {$lineNumber}: rating must be between 1 and 5.";
                continue;
            }

            if ($reviewerName === '') {
                $errors[] = "Line {$lineNumber}: reviewer name is required.";
                continue;
            }

            $parsedDate = null;
            if ($dateRaw !== null) {
                try {
                    $parsedDate = CarbonImmutable::parse($dateRaw)->toDateString();
                } catch (\Throwable) {
                    $errors[] = "Line {$lineNumber}: invalid date '{$dateRaw}'.";
                    continue;
                }
            }

            $rows[] = [
                'line' => $lineNumber,
                'rating' => $rating,
                'reviewer_name' => mb_substr($reviewerName, 0, 120),
                'title' => $title !== null ? mb_substr($title, 0, 120) : null,
                'body' => $body !== null ? mb_substr($body, 0, 2000) : null,
                'original_reviewed_at' => $parsedDate,
            ];
        }

        return [
            'rows' => $rows,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<int, array{
     *   rating:int,
     *   reviewer_name:string,
     *   title:?string,
     *   body:?string,
     *   original_reviewed_at:?string
     * }>  $rows
     */
    public function importRows(Course $course, User $adminUser, array $rows): int
    {
        $created = 0;

        foreach ($rows as $row) {
            CourseReview::query()->create([
                'course_id' => $course->id,
                'user_id' => null,
                'source' => CourseReview::SOURCE_UDEMY_MANUAL,
                'reviewer_name' => $row['reviewer_name'],
                'rating' => (int) $row['rating'],
                'title' => $row['title'] ?: null,
                'body' => $row['body'] ?: null,
                'status' => CourseReview::STATUS_APPROVED,
                'last_submitted_at' => now(),
                'approved_at' => now(),
                'approved_by_user_id' => $adminUser->id,
                'original_reviewed_at' => $row['original_reviewed_at'] ?: null,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * @return array<int, string>
     */
    private function splitLine(string $line): array
    {
        if (str_contains($line, "\t")) {
            return array_map('trim', explode("\t", $line));
        }

        if (str_contains($line, '|')) {
            return array_map('trim', explode('|', $line));
        }

        return array_map('trim', str_getcsv($line));
    }

    private function nullableTrimmed(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}

