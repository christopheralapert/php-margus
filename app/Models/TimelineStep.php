<?php

namespace App\Models;

use App\Enums\TimelinePhase;
use Database\Factories\TimelineStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class TimelineStep extends Model
{
    /** @use HasFactory<TimelineStepFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['procedure_id', 'phase', 'marker', 'title', 'body', 'sort'];

    public array $translatable = ['marker', 'title', 'body'];

    protected function casts(): array
    {
        return [
            'phase' => TimelinePhase::class,
            'sort' => 'integer',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }
}
