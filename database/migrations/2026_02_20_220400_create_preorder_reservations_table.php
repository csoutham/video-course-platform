<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('preorder_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->index();
            $table->string('stripe_customer_id')->index();
            $table->string('stripe_setup_intent_id')->unique();
            $table->string('stripe_payment_method_id');
            $table->unsignedInteger('reserved_price_amount');
            $table->string('currency', 3)->default('usd');
            $table->string('status', 32)->default('reserved')->index();
            $table->timestamp('release_at')->index();
            $table->foreignId('charged_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestamp('charged_at')->nullable();
            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preorder_reservations');
    }
};
