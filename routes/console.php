<?php

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Entitlement;
use App\Models\Order;
use App\Models\StripeEvent;
use App\Models\User;
use App\Services\Learning\CloudflareStreamMetadataService;
use App\Services\Payments\EntitlementService;
use App\Services\Payments\StripeWebhookService;
use Illuminate\Support\Facades\Artisan;

Artisan::command('videocourses:stripe-reprocess {event_id}', function ($event_id): int {
    $event = StripeEvent::query()->firstWhere('stripe_event_id', $event_id);

    if (! $event) {
        $this->error("Stripe event not found: {$event_id}");

        return self::FAILURE;
    }

    $webhookService = resolve(StripeWebhookService::class);
    $webhookService->reprocessStoredEvent($event);
    $this->info("Reprocessed Stripe event: {$event_id}");

    return self::SUCCESS;
})->purpose('Reprocess a stored Stripe event by stripe_event_id');

Artisan::command('videocourses:entitlement-grant {user_id} {course_id} {order_id}', function ($user_id, $course_id, $order_id): int {
    $user = User::query()->find((int) $user_id);
    $course = Course::query()->find((int) $course_id);
    $order = Order::query()->find((int) $order_id);

    if (! $user || ! $course || ! $order) {
        $this->error('Invalid user, course, or order identifier.');

        return self::FAILURE;
    }

    $order->forceFill([
        'user_id' => $user->id,
        'email' => $user->email,
    ])->save();
    $order->items()->updateOrCreate(
        ['course_id' => $course->id],
        ['unit_amount' => $order->total_amount, 'quantity' => 1]
    );

    $entitlementService = resolve(EntitlementService::class);
    $entitlementService->grantForOrder($order);

    $this->info("Granted entitlement for user {$user_id} course {$course_id} via order {$order_id}.");

    return self::SUCCESS;
})->purpose('Manually grant entitlement using user, course, and order IDs');

Artisan::command('videocourses:entitlement-revoke {user_id} {course_id}', function ($user_id, $course_id): int {
    $updated = Entitlement::query()
        ->where('user_id', (int) $user_id)
        ->where('course_id', (int) $course_id)
        ->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'updated_at' => now(),
        ]);

    if ($updated === 0) {
        $this->error('No matching entitlement found.');

        return self::FAILURE;
    }

    $this->info("Revoked {$updated} entitlement(s) for user {$user_id} course {$course_id}.");

    return self::SUCCESS;
})->purpose('Manually revoke entitlement by user and course IDs');

Artisan::command('videocourses:stream-sync-durations {--course_id=} {--force}', function (): int {
    $query = CourseLesson::query()
        ->whereNotNull('stream_video_id')
        ->where('stream_video_id', '!=', '');

    if (! $this->option('force')) {
        $query->whereNull('duration_seconds');
    }

    $courseId = $this->option('course_id');

    if ($courseId) {
        $query->where('course_id', (int) $courseId);
    }

    $lessons = $query->get();

    if ($lessons->isEmpty()) {
        $this->info('No lessons matched sync criteria.');

        return self::SUCCESS;
    }

    $metadataService = resolve(CloudflareStreamMetadataService::class);
    $updated = 0;

    foreach ($lessons as $lesson) {
        try {
            $durationSeconds = $metadataService->durationSeconds((string) $lesson->stream_video_id);
        } catch (\Throwable $exception) {
            $this->error('Failed for lesson '.$lesson->id.' ('.$lesson->slug.'): '.$exception->getMessage());

            return self::FAILURE;
        }

        $lesson->forceFill([
            'duration_seconds' => $durationSeconds,
        ])->save();

        $updated++;
    }

    $this->info('Updated durations for '.$updated.' lesson(s).');

    return self::SUCCESS;
})->purpose('Sync lesson durations from Cloudflare Stream metadata API');
