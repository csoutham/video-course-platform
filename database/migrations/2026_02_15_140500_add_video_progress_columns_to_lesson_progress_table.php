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
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->unsignedInteger('playback_position_seconds')->default(0)->after('status');
            $table->unsignedInteger('video_duration_seconds')->nullable()->after('playback_position_seconds');
            $table->unsignedTinyInteger('percent_complete')->default(0)->after('video_duration_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropColumn([
                'playback_position_seconds',
                'video_duration_seconds',
                'percent_complete',
            ]);
        });
    }
};
