<?php

namespace App\Services\Payments;

use App\Models\Course;
use Stripe\StripeClient;

class StripeCoursePricingService
{
    public function createPriceForCourse(Course $course): string
    {
        $stripe = new StripeClient((string) config('services.stripe.secret'));

        $product = $stripe->products->create([
            'name' => $course->title,
            'description' => $course->description ?: null,
            'images' => $course->thumbnail_url ? [$course->thumbnail_url] : null,
            'metadata' => [
                'course_id' => (string) $course->id,
                'course_slug' => $course->slug,
                'source' => 'videocourses-admin',
            ],
        ]);

        $price = $stripe->prices->create([
            'product' => (string) $product->id,
            'unit_amount' => $course->price_amount,
            'currency' => strtolower($course->price_currency),
            'metadata' => [
                'course_id' => (string) $course->id,
                'course_slug' => $course->slug,
            ],
        ]);

        return (string) $price->id;
    }
}
