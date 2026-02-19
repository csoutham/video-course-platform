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
            $table->unsignedSmallInteger('logo_height_px')->nullable()->after('logo_url');
            $table->string('publisher_name', 120)->nullable()->after('font_weights');
            $table->string('publisher_website', 255)->nullable()->after('publisher_name');
            $table->string('footer_tagline', 255)->nullable()->after('publisher_website');
            $table->string('homepage_eyebrow', 80)->nullable()->after('footer_tagline');
            $table->string('homepage_title', 160)->nullable()->after('homepage_eyebrow');
            $table->string('homepage_subtitle', 500)->nullable()->after('homepage_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branding_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'logo_height_px',
                'publisher_name',
                'publisher_website',
                'footer_tagline',
                'homepage_eyebrow',
                'homepage_title',
                'homepage_subtitle',
            ]);
        });
    }
};
