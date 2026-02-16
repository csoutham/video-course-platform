<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Learning\CloudflareStreamCatalogService;
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

    public function create(): View
    {
        return view('admin.courses.create');
    }

    public function store(Request $request, StripeCoursePricingService $pricingService): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:courses,slug'],
            'description' => ['nullable', 'string'],
            'thumbnail_url' => ['nullable', 'url', 'max:2048'],
            'price_amount' => ['required', 'integer', 'min:100'],
            'price_currency' => ['required', 'string', 'in:usd,gbp'],
            'is_published' => ['nullable', 'boolean'],
            'auto_create_stripe_price' => ['nullable', 'boolean'],
        ]);

        $course = DB::transaction(fn (): Course => Course::query()->create([
            'title' => $validated['title'],
            'slug' => $this->resolveCourseSlug($validated['slug'] ?? null, $validated['title']),
            'description' => $validated['description'] ?? null,
            'thumbnail_url' => $validated['thumbnail_url'] ?? null,
            'price_amount' => (int) $validated['price_amount'],
            'price_currency' => strtolower((string) $validated['price_currency']),
            'is_published' => (bool) ($validated['is_published'] ?? false),
        ]));

        if ((bool) ($validated['auto_create_stripe_price'] ?? true)) {
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

        $streamVideos = [];
        $streamCatalogStatus = null;

        try {
            $streamVideos = $streamCatalogService->listVideos(200);
        } catch (RuntimeException $exception) {
            $streamCatalogStatus = $exception->getMessage();
        }

        return view('admin.courses.edit', [
            'course' => $course,
            'streamVideos' => $streamVideos,
            'streamCatalogStatus' => $streamCatalogStatus,
        ]);
    }

    public function update(Course $course, Request $request, StripeCoursePricingService $pricingService): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:courses,slug,'.$course->id],
            'description' => ['nullable', 'string'],
            'thumbnail_url' => ['nullable', 'url', 'max:2048'],
            'price_amount' => ['required', 'integer', 'min:100'],
            'price_currency' => ['required', 'string', 'in:usd,gbp'],
            'stripe_price_id' => ['nullable', 'string', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
            'refresh_stripe_price' => ['nullable', 'boolean'],
        ]);

        $course->forceFill([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'thumbnail_url' => $validated['thumbnail_url'] ?? null,
            'price_amount' => (int) $validated['price_amount'],
            'price_currency' => strtolower((string) $validated['price_currency']),
            'stripe_price_id' => $validated['stripe_price_id'] ?: null,
            'is_published' => (bool) ($validated['is_published'] ?? false),
        ])->save();

        if ((bool) ($validated['refresh_stripe_price'] ?? false)) {
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
}
