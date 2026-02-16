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
        Schema::table('courses', function (Blueprint $table) {
            $table->string('source_platform')->nullable()->after('id')->index();
            $table->string('source_url')->nullable()->after('source_platform')->unique();
            $table->string('source_external_id')->nullable()->after('source_url')->index();
            $table->json('source_payload_json')->nullable()->after('thumbnail_url');
            $table->timestamp('source_last_imported_at')->nullable()->after('source_payload_json');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'source_platform',
                'source_url',
                'source_external_id',
                'source_payload_json',
                'source_last_imported_at',
            ]);
        });
    }
};
