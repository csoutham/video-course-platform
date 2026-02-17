<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->boolean('is_free')->default(false)->after('stripe_price_id');
            $table->string('free_access_mode', 24)->default('claim_link')->after('is_free');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn(['is_free', 'free_access_mode']);
        });
    }
};

