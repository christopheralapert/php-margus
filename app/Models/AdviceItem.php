<?php

namespace App\Models;

use App\Enums\AdviceSection;
use Database\Factories\AdviceItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class AdviceItem extends Model
{
    /** @use HasFactory<AdviceItemFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['procedure_id', 'section', 'text', 'merge_key', 'sort'];

    public array $translatable = ['text'];

    protected function casts(): array
    {
        return [
            'section' => AdviceSection::class,
            'sort' => 'integer',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }
}
