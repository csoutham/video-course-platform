<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'sort_order',
        'is_imported_shell',
        'source_external_key',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'is_imported_shell' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class, 'module_id')->orderBy('sort_order');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(LessonResource::class, 'module_id')
            ->whereNull('lesson_id')
            ->orderBy('sort_order');
    }
}
