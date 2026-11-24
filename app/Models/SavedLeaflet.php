<?php

namespace App\Models;

use App\Enums\LeafletFormat;
use Database\Factories\SavedLeafletFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SavedLeaflet extends Model
{
    /** @use HasFactory<SavedLeafletFactory> */
    use HasFactory;

    protected $fillable = ['uuid', 'user_id', 'locale', 'procedure_ids', 'first_name', 'procedure_date', 'format', 'last_downloaded_at'];

    protected function casts(): array
    {
        return [
            'procedure_ids' => 'array',
            'first_name' => 'encrypted',
            'procedure_date' => 'encrypted',
            'format' => LeafletFormat::class,
            'last_downloaded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SavedLeaflet $leaflet): void {
            $leaflet->uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
