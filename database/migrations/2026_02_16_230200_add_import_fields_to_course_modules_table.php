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
        Schema::table('course_modules', function (Blueprint $table) {
            $table->boolean('is_imported_shell')->default(false)->after('sort_order');
            $table->string('source_external_key')->nullable()->after('is_imported_shell');

            $table->index(['course_id', 'is_imported_shell']);
            $table->index(['course_id', 'source_external_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            $table->dropColumn([
                'is_imported_shell',
                'source_external_key',
            ]);
        });
    }
};
