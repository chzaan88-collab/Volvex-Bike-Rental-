<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ride extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'bike_id', 'started_at', 'due_at', 'ended_at', 'cost', 'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'due_at' => 'datetime',
            'ended_at' => 'datetime',
            'cost' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bike(): BelongsTo
    {
        return $this->belongsTo(Bike::class);
    }

    public function calculateCost(): float
    {
        $end = $this->ended_at ?? now();
        $hours = max(1, (int) ceil($this->started_at->diffInMinutes($end) / 60));

        return round($hours * (float) $this->bike->hourly_rate, 2);
    }
}
