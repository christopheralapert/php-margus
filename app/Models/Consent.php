<?php

namespace App\Models;

use App\Enums\ConsentPurpose;
use Database\Factories\ConsentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consent extends Model
{
    /** @use HasFactory<ConsentFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'purpose', 'text_version', 'locale', 'given_at', 'withdrawn_at'];

    protected function casts(): array
    {
        return [
            'purpose' => ConsentPurpose::class,
            'given_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
