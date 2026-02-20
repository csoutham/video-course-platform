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

    public function createPreorderPriceForCourse(Course $course): string
    {
        $stripe = new StripeClient((string) config('services.stripe.secret'));

        $price = $stripe->prices->create([
            'unit_amount' => (int) $course->preorder_price_amount,
            'currency' => strtolower($course->price_currency),
            'product_data' => $this->productData($course),
            'metadata' => [
                'course_id' => (string) $course->id,
                'course_slug' => $course->slug,
                'flow' => 'preorder',
            ],
        ]);

        return (string) $price->id;
    }

    /**
     * @return array{name: string, metadata: array<string, string>}
     */
    private function productData(Course $course): array
    {
        return [
            'name' => $course->title,
            'metadata' => [
                'course_id' => (string) $course->id,
                'course_slug' => $course->slug,
                'source' => 'videocourses-admin',
            ],
        ];
    }
}
