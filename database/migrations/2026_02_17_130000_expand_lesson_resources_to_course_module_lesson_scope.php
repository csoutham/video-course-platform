<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('lesson_resources', function (Blueprint $table): void {
            $table->foreignId('course_id')->nullable()->after('id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->after('course_id')->constrained('course_modules')->cascadeOnDelete();
        });

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
            DB::statement('CREATE TABLE lesson_resources_tmp (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                course_id INTEGER NULL,
                module_id INTEGER NULL,
                lesson_id INTEGER NULL,
                name VARCHAR NOT NULL,
                storage_key VARCHAR NOT NULL,
                mime_type VARCHAR NULL,
                size_bytes INTEGER NULL,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY(module_id) REFERENCES course_modules(id) ON DELETE CASCADE,
                FOREIGN KEY(lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE
            )');
            DB::statement('INSERT INTO lesson_resources_tmp (id, course_id, module_id, lesson_id, name, storage_key, mime_type, size_bytes, sort_order, created_at, updated_at)
                SELECT id, course_id, module_id, lesson_id, name, storage_key, mime_type, size_bytes, sort_order, created_at, updated_at
                FROM lesson_resources');
            DB::statement('DROP TABLE lesson_resources');
            DB::statement('ALTER TABLE lesson_resources_tmp RENAME TO lesson_resources');
            DB::statement('CREATE INDEX lesson_resources_lesson_id_sort_order_index ON lesson_resources (lesson_id, sort_order)');
            DB::statement('CREATE INDEX lesson_resources_course_id_sort_order_index ON lesson_resources (course_id, sort_order)');
            DB::statement('CREATE INDEX lesson_resources_module_id_sort_order_index ON lesson_resources (module_id, sort_order)');
            DB::statement('PRAGMA foreign_keys=ON');
        } else {
            Schema::table('lesson_resources', function (Blueprint $table): void {
                $table->foreignId('lesson_id')->nullable()->change();
                $table->index(['course_id', 'sort_order']);
                $table->index(['module_id', 'sort_order']);
            });
        }

        $rows = DB::table('lesson_resources')
            ->whereNotNull('lesson_id')
            ->get(['id', 'lesson_id']);

        foreach ($rows as $row) {
            $lesson = DB::table('course_lessons')
                ->where('id', $row->lesson_id)
                ->first(['course_id', 'module_id']);

            if (! $lesson) {
                continue;
            }

            DB::table('lesson_resources')
                ->where('id', $row->id)
                ->update([
                    'course_id' => $lesson->course_id,
                    'module_id' => $lesson->module_id,
                ]);
        }
    }

    public function down(): void
    {
        // Keep expanded schema; destructive rollback of scoped resources is intentionally omitted.
    }
};

