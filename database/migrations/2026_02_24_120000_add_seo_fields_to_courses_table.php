<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->string('seo_title', 160)->nullable()->after('description');
            $table->string('seo_description', 320)->nullable()->after('seo_title');
            $table->string('seo_image_url')->nullable()->after('seo_description');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn(['seo_title', 'seo_description', 'seo_image_url']);
        });
    }
};
