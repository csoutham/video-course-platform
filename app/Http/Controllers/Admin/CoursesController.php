<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Learning\CloudflareStreamCatalogService;
use App\Services\Learning\CloudflareStreamMetadataService;
use App\Services\Payments\StripeCoursePricingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CoursesController extends Controller
{
    public function index(): View
    {
        $courses = Course::query()
            ->withCount(['modules', 'lessons'])
            ->orderBy('title')
            ->paginate(20);

        return view('admin.courses.index', [
            'courses' => $courses,
        ]);
    }

    public function create(CloudflareStreamCatalogService $streamCatalogService): View
    {
        [$streamVideos, $streamCatalogStatus, $streamCatalogFilterNotice] = $this->resolveStreamCatalog($streamCatalogService);

        return view('admin.courses.create', [
            'streamVideos' => $streamVideos,
            'streamCatalogStatus' => $streamCatalogStatus,
            'streamCatalogFilterNotice' => $streamCatalogFilterNotice,
        ]);
    }

    public function store(
        Request $request,
        StripeCoursePricingService $pricingService,
        CloudflareStreamMetadataService $metadataService,
    ): RedirectResponse {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:courses,slug'],
            'description' => ['nullable', 'string'],
            'long_description' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'thumbnail_url' => ['nullable', 'url', 'max:2048'],
            'intro_video_id' => ['nullable', 'string', 'max:255'],
            'stream_video_filter_term' => ['nullable', 'string', 'max:255'],
            'price_amount' => ['nullable', 'integer', 'min:0'],
            'price_currency' => ['required', 'string', 'in:usd,gbp'],
            'is_published' => ['nullable', 'boolean'],
            'is_free' => ['nullable', 'boolean'],
            'free_access_mode' => ['nullable', 'string', 'in:direct,claim_link'],
            'auto_create_stripe_price' => ['nullable', 'boolean'],
        ]);

        $isFree = (bool) ($validated['is_free'] ?? false);
        $priceAmount = (int) ($validated['price_amount'] ?? 0);

        if (! $isFree && $priceAmount < 100) {
            return back()
                ->withInput()
                ->withErrors(['price_amount' => 'Paid courses must be at least 100 (cents/pence).']);
        }

        $introVideoId = ($validated['intro_video_id'] ?? null) ?: null;
        if ($introVideoId) {
            try {
                $metadataService->requireSignedUrls($introVideoId);
            } catch (RuntimeException $exception) {
                return back()
                    ->withInput()
                    ->withErrors(['intro_video_id' => $exception->getMessage()]);
            }
        }

        $course = DB::transaction(fn (): Course => Course::query()->create([
            'title' => $validated['title'],
            'slug' => $this->resolveCourseSlug($validated['slug'] ?? null, $validated['title']),
            'description' => $validated['description'] ?? null,
            'long_description' => $validated['long_description'] ?? null,
            'requirements' => $validated['requirements'] ?? null,
            'thumbnail_url' => $validated['thumbnail_url'] ?? null,
            'intro_video_id' => $introVideoId,
            'stream_video_filter_term' => ($validated['stream_video_filter_term'] ?? null) ?: null,
            'price_amount' => $isFree ? 0 : $priceAmount,
            'price_currency' => strtolower((string) $validated['price_currency']),
            'is_free' => $isFree,
            'free_access_mode' => (string) ($validated['free_access_mode'] ?? 'claim_link'),
            'stripe_price_id' => null,
            'is_published' => (bool) ($validated['is_published'] ?? false),
        ]));

        if (! $isFree && (bool) ($validated['auto_create_stripe_price'] ?? true)) {
            try {
                $stripePriceId = $pricingService->createPriceForCourse($course);
                $course->forceFill([
                    'stripe_price_id' => $stripePriceId,
                ])->save();
            } catch (Throwable $exception) {
                Log::warning('admin_stripe_price_provision_failed', [
                    'course_id' => $course->id,
                    'message' => $exception->getMessage(),
                ]);

                return to_route('admin.courses.edit', $course)
                    ->with('status', 'Course created, but Stripe price provisioning failed: '.$exception->getMessage());
            }
        }

        return to_route('admin.courses.edit', $course)
            ->with('status', 'Course created.');
    }

    public function edit(Course $course, CloudflareStreamCatalogService $streamCatalogService): View
    {
        $course->load([
            'modules.lessons' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        [$streamVideos, $streamCatalogStatus, $streamCatalogFilterNotice] = $this->resolveStreamCatalog($streamCatalogService, $course);

        return view('admin.courses.edit', [
            'course' => $course,
            'streamVideos' => $streamVideos,
            'streamCatalogStatus' => $streamCatalogStatus,
            'streamCatalogFilterNotice' => $streamCatalogFilterNotice,
        ]);
    }

    public function update(
        Course $course,
        Request $request,
        StripeCoursePricingService $pricingService,
        CloudflareStreamMetadataService $metadataService,
    ): RedirectResponse {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:courses,slug,'.$course->id],
            'description' => ['nullable', 'string'],
            'long_description' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'thumbnail_url' => ['nullable', 'url', 'max:2048'],
            'intro_video_id' => ['nullable', 'string', 'max:255'],
            'stream_video_filter_term' => ['nullable', 'string', 'max:255'],
            'price_amount' => ['nullable', 'integer', 'min:0'],
            'price_currency' => ['required', 'string', 'in:usd,gbp'],
            'stripe_price_id' => ['nullable', 'string', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
            'is_free' => ['nullable', 'boolean'],
            'free_access_mode' => ['nullable', 'string', 'in:direct,claim_link'],
            'refresh_stripe_price' => ['nullable', 'boolean'],
        ]);

        $isFree = (bool) ($validated['is_free'] ?? false);
        $priceAmount = (int) ($validated['price_amount'] ?? 0);

        if (! $isFree && $priceAmount < 100) {
            return back()
                ->withInput()
                ->withErrors(['price_amount' => 'Paid courses must be at least 100 (cents/pence).']);
        }

        $introVideoId = ($validated['intro_video_id'] ?? null) ?: null;
        if ($introVideoId) {
            try {
                $metadataService->requireSignedUrls($introVideoId);
            } catch (RuntimeException $exception) {
                return back()
                    ->withInput()
                    ->withErrors(['intro_video_id' => $exception->getMessage()]);
            }
        }

        $course->forceFill([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'long_description' => $validated['long_description'] ?? null,
            'requirements' => $validated['requirements'] ?? null,
            'thumbnail_url' => $validated['thumbnail_url'] ?? null,
            'intro_video_id' => $introVideoId,
            'stream_video_filter_term' => ($validated['stream_video_filter_term'] ?? null) ?: null,
            'price_amount' => $isFree ? 0 : $priceAmount,
            'price_currency' => strtolower((string) $validated['price_currency']),
            'stripe_price_id' => $isFree ? null : ($validated['stripe_price_id'] ?: null),
            'is_free' => $isFree,
            'free_access_mode' => (string) ($validated['free_access_mode'] ?? 'claim_link'),
            'is_published' => (bool) ($validated['is_published'] ?? false),
        ])->save();

        if (! $isFree && (bool) ($validated['refresh_stripe_price'] ?? false)) {
            try {
                $course->forceFill([
                    'stripe_price_id' => $pricingService->createPriceForCourse($course),
                ])->save();
            } catch (Throwable $exception) {
                Log::warning('admin_stripe_price_refresh_failed', [
                    'course_id' => $course->id,
                    'message' => $exception->getMessage(),
                ]);

                return to_route('admin.courses.edit', $course)
                    ->with('status', 'Course updated, but Stripe price refresh failed: '.$exception->getMessage());
            }
        }

        return to_route('admin.courses.edit', $course)
            ->with('status', 'Course updated.');
    }

    private function resolveCourseSlug(?string $slug, string $title): string
    {
        $base = Str::of($slug ?: $title)->slug()->value();
        $base = $base !== '' ? $base : 'course';
        $candidate = $base;
        $counter = 1;

        while (Course::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    /**
     * @return array{0: array<int, array{uid: string, name: string, duration_seconds: int|null}>, 1: string|null, 2: string|null}
     */
    private function resolveStreamCatalog(CloudflareStreamCatalogService $streamCatalogService, ?Course $course = null): array
    {
        $streamVideos = [];
        $streamCatalogStatus = null;
        $streamCatalogFilterNotice = null;

        try {
            $streamVideos = $streamCatalogService->listVideos(200);

            if ($course) {
                $rawFilterTerm = (string) ($course->stream_video_filter_term ?: '');
                $resolvedFilterTerm = Str::of($rawFilterTerm)->squish()->value();

                if ($resolvedFilterTerm !== '') {
                    $normalizedFilterTerm = Str::lower($resolvedFilterTerm);
                    $filteredVideos = collect($streamVideos)
                        ->filter(fn (array $video): bool => str_contains(Str::lower((string) ($video['name'] ?? '')), $normalizedFilterTerm))
                        ->values()
                        ->all();

                    if (count($filteredVideos) > 0) {
                        $streamVideos = $filteredVideos;
                        $streamCatalogFilterNotice = sprintf(
                            'Showing %d Stream video%s filtered by course filter: "%s".',
                            count($filteredVideos),
                            count($filteredVideos) === 1 ? '' : 's',
                            $resolvedFilterTerm
                        );
                    } else {
                        $streamCatalogFilterNotice = sprintf(
                            'No Stream videos matched filter "%s". Showing full catalog list instead.',
                            $resolvedFilterTerm
                        );
                    }
                }
            }
        } catch (RuntimeException $exception) {
            $streamCatalogStatus = $exception->getMessage();
        }

        return [$streamVideos, $streamCatalogStatus, $streamCatalogFilterNotice];
    }
}
