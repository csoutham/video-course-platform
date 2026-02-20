<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('billing_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('stripe_subscription_monthly_price_id')->nullable();
            $table->string('stripe_subscription_yearly_price_id')->nullable();
            $table->string('subscription_currency', 3)->default('usd');
            $table->boolean('stripe_billing_portal_enabled')->default(false);
            $table->string('stripe_billing_portal_configuration_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_settings');
    }
};
