<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('stripe_checkout_session_id')->unique();
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('subtotal_amount')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('total_amount')->default(0);
            $table->string('currency', 3)->default('usd');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
