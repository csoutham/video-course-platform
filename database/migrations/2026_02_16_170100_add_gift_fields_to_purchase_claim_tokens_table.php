<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_claim_tokens', function (Blueprint $table) {
            $table->string('purpose', 24)->default('order_claim')->index()->after('token');
            $table->foreignId('gift_purchase_id')->nullable()->unique()->after('order_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_claim_tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gift_purchase_id');
            $table->dropColumn('purpose');
        });
    }
};
