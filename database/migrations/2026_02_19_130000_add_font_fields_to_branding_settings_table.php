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
        Schema::table('branding_settings', function (Blueprint $table): void {
            $table->string('font_provider', 20)->nullable()->after('logo_url');
            $table->string('font_family', 120)->nullable()->after('font_provider');
            $table->string('font_weights', 80)->nullable()->after('font_family');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branding_settings', function (Blueprint $table): void {
            $table->dropColumn(['font_provider', 'font_family', 'font_weights']);
        });
    }
};
