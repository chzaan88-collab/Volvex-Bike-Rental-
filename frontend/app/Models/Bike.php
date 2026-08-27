<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bike extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id', 'name', 'model', 'license', 'image_url',
        'hourly_rate', 'battery', 'last_known_address',
        'latitude', 'longitude', 'status',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function rides(): HasMany
    {
        return $this->hasMany(Ride::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'AVAILABLE');
    }
}
