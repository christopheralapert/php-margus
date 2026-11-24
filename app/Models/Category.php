<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['slug', 'name', 'icon_path', 'color', 'sort', 'is_active'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function procedures(): HasMany
    {
        return $this->hasMany(Procedure::class)->orderBy('sort');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
