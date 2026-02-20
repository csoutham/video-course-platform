<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->unsignedInteger('reviews_approved_count')->default(0)->after('stripe_preorder_price_id');
            $table->decimal('rating_average', 3, 2)->nullable()->after('reviews_approved_count');
            $table->json('rating_distribution_json')->nullable()->after('rating_average');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn([
                'reviews_approved_count',
                'rating_average',
                'rating_distribution_json',
            ]);
        });
    }
};

