<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->boolean('is_subscription_excluded')->default(false)->after('is_published');
            $table->boolean('is_preorder_enabled')->default(false)->after('is_subscription_excluded');
            $table->timestamp('preorder_starts_at')->nullable()->after('is_preorder_enabled');
            $table->timestamp('preorder_ends_at')->nullable()->after('preorder_starts_at');
            $table->timestamp('release_at')->nullable()->after('preorder_ends_at');
            $table->unsignedInteger('preorder_price_amount')->nullable()->after('release_at');
            $table->string('stripe_preorder_price_id')->nullable()->after('preorder_price_amount');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn([
                'is_subscription_excluded',
                'is_preorder_enabled',
                'preorder_starts_at',
                'preorder_ends_at',
                'release_at',
                'preorder_price_amount',
                'stripe_preorder_price_id',
            ]);
        });
    }
};
