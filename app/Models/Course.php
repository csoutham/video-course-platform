<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_platform',
        'source_url',
        'source_external_id',
        'slug',
        'title',
        'description',
        'long_description',
        'requirements',
        'thumbnail_url',
        'intro_video_id',
        'stream_video_filter_term',
        'source_payload_json',
        'source_last_imported_at',
        'price_amount',
        'price_currency',
        'stripe_price_id',
        'is_free',
        'free_access_mode',
        'is_published',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_free' => 'boolean',
            'source_payload_json' => 'array',
            'source_last_imported_at' => 'datetime',
        ];
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('sort_order');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class)->orderBy('sort_order');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(LessonResource::class)
            ->whereNull('module_id')
            ->whereNull('lesson_id')
            ->orderBy('sort_order');
    }

    protected function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
