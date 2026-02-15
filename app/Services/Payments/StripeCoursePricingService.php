<?php

namespace App\Services\Payments;

use App\Models\Course;
use Stripe\StripeClient;

class StripeCoursePricingService
{
    public function createPriceForCourse(Course $course): string
    {
        $stripe = new StripeClient((string) config('services.stripe.secret'));

        $price = $stripe->prices->create([
            'unit_amount' => $course->price_amount,
            'currency' => strtolower($course->price_currency),
            'product_data' => $this->productData($course),
            'metadata' => [
                'course_id' => (string) $course->id,
                'course_slug' => $course->slug,
            ],
        ]);

        return (string) $price->id;
    }

    /**
     * @return array{name: string, description?: string, metadata: array<string, string>}
     */
    private function productData(Course $course): array
    {
        $payload = [
            'name' => $course->title,
            'metadata' => [
                'course_id' => (string) $course->id,
                'course_slug' => $course->slug,
                'source' => 'videocourses-admin',
            ],
        ];

        if ($course->description) {
            $payload['description'] = $course->description;
        }

        return $payload;
    }
}
