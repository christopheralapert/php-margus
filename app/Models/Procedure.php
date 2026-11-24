<?php

namespace App\Models;

use App\Enums\AdviceSection;
use App\Enums\ProcedureStatus;
use Database\Factories\ProcedureFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Procedure extends Model
{
    /** @use HasFactory<ProcedureFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['category_id', 'slug', 'name', 'summary', 'color', 'illustration_path', 'cover_illustration_path', 'status', 'review_note', 'reviewed_by', 'reviewed_at', 'search_keywords', 'follow_up', 'sort', 'is_active'];

    public array $translatable = ['name', 'summary', 'review_note', 'search_keywords', 'follow_up'];

    protected function casts(): array
    {
        return [
            'status' => ProcedureStatus::class,
            'reviewed_at' => 'datetime',
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (! array_key_exists('review_note', $this->attributes)) {
            $this->setTranslations('review_note', [
                'et' => __('models.procedure_review_note', [], 'et'),
                'en' => __('models.procedure_review_note', [], 'en'),
            ]);
        }
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function timelineSteps(): HasMany
    {
        return $this->hasMany(TimelineStep::class)
            ->orderByRaw("CASE phase WHEN 'first_24h' THEN 0 WHEN 'days_1_3' THEN 1 WHEN 'week_1' THEN 2 WHEN 'full_recovery' THEN 3 END")
            ->orderBy('sort');
    }

    public function adviceItems(): HasMany
    {
        return $this->hasMany(AdviceItem::class)->orderBy('sort');
    }

    public function adviceFor(AdviceSection $section): HasMany
    {
        return $this->adviceItems()->where('section', $section->value);
    }

    public function themeColor(): string
    {
        return $this->color ?? $this->category->color;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProcedureStatus::Published);
    }
}
