<?php

namespace App\Models;

use App\Support\PublicMediaUrl;
use Carbon\CarbonImmutable;
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
        'kit_tag_id',
        'source_payload_json',
        'source_last_imported_at',
        'price_amount',
        'price_currency',
        'stripe_price_id',
        'is_free',
        'free_access_mode',
        'is_published',
        'is_subscription_excluded',
        'is_preorder_enabled',
        'certificate_enabled',
        'certificate_template_path',
        'certificate_signatory_name',
        'certificate_signatory_title',
        'preorder_starts_at',
        'preorder_ends_at',
        'release_at',
        'preorder_price_amount',
        'stripe_preorder_price_id',
        'reviews_approved_count',
        'rating_average',
        'rating_distribution_json',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_free' => 'boolean',
            'is_subscription_excluded' => 'boolean',
            'is_preorder_enabled' => 'boolean',
            'certificate_enabled' => 'boolean',
            'preorder_starts_at' => 'datetime',
            'preorder_ends_at' => 'datetime',
            'release_at' => 'datetime',
            'source_payload_json' => 'array',
            'source_last_imported_at' => 'datetime',
            'kit_tag_id' => 'integer',
            'reviews_approved_count' => 'integer',
            'rating_average' => 'decimal:2',
            'rating_distribution_json' => 'array',
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

    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class)->latest('approved_at');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(CourseCertificate::class);
    }

    protected function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    protected function getThumbnailUrlAttribute(?string $value): ?string
    {
        return PublicMediaUrl::resolve($value);
    }

    public function isReleased(): bool
    {
        if (! $this->release_at) {
            return true;
        }

        return CarbonImmutable::now()->greaterThanOrEqualTo($this->release_at->toImmutable());
    }

    public function isPreorderWindowActive(): bool
    {
        if (! $this->is_preorder_enabled) {
            return false;
        }

        $now = CarbonImmutable::now();
        $startsAt = $this->preorder_starts_at?->toImmutable();
        $endsAt = $this->preorder_ends_at?->toImmutable();

        if ($startsAt && $now->lessThan($startsAt)) {
            return false;
        }

        if ($endsAt && $now->greaterThan($endsAt)) {
            return false;
        }

        return true;
    }
}
