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
        Schema::create('stripe_events', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_event_id')->unique();
            $table->string('event_type');
            $table->json('payload_json');
            $table->timestamp('processed_at')->nullable()->index();
            $table->text('processing_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_events');
    }
};
