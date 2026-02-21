<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->boolean('certificate_enabled')->default(false)->after('is_preorder_enabled');
            $table->string('certificate_template_path')->nullable()->after('certificate_enabled');
            $table->string('certificate_signatory_name')->nullable()->after('certificate_template_path');
            $table->string('certificate_signatory_title')->nullable()->after('certificate_signatory_name');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn([
                'certificate_enabled',
                'certificate_template_path',
                'certificate_signatory_name',
                'certificate_signatory_title',
            ]);
        });
    }
};
