<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branding_settings', function (Blueprint $table): void {
            $table->string('homepage_seo_title', 160)->nullable()->after('homepage_subtitle');
            $table->string('homepage_seo_description', 320)->nullable()->after('homepage_seo_title');
            $table->string('analytics_provider', 20)->nullable()->after('homepage_seo_description');
            $table->string('analytics_site_id', 120)->nullable()->after('analytics_provider');
            $table->string('analytics_script_url')->nullable()->after('analytics_site_id');
            $table->text('analytics_custom_head_snippet')->nullable()->after('analytics_script_url');
        });
    }

    public function down(): void
    {
        Schema::table('branding_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'homepage_seo_title',
                'homepage_seo_description',
                'analytics_provider',
                'analytics_site_id',
                'analytics_script_url',
                'analytics_custom_head_snippet',
            ]);
        });
    }
};
