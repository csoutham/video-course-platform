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
        Schema::create('branding_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('platform_name', 120);
            $table->string('logo_url')->nullable();
            $table->string('color_bg', 7)->nullable();
            $table->string('color_panel', 7)->nullable();
            $table->string('color_panel_soft', 7)->nullable();
            $table->string('color_border', 7)->nullable();
            $table->string('color_text', 7)->nullable();
            $table->string('color_muted', 7)->nullable();
            $table->string('color_brand', 7)->nullable();
            $table->string('color_brand_strong', 7)->nullable();
            $table->string('color_accent', 7)->nullable();
            $table->string('color_warning', 7)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branding_settings');
    }
};
