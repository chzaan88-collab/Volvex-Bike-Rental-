<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'title', 'description', 'discount_type', 'discount_value', 'expires_at'];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function claimedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'offer_user')
            ->withPivot('claimed_at')
            ->withTimestamps();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
