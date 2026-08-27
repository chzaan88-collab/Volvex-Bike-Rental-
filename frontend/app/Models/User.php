<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'account_mode',
        'rider_status',
        'current_balance',
        'lifetime_spend',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'current_balance' => 'decimal:2',
            'lifetime_spend' => 'decimal:2',
        ];
    }

    public function rides(): HasMany
    {
        return $this->hasMany(Ride::class);
    }

    public function bikes(): HasMany
    {
        return $this->hasMany(Bike::class, 'owner_id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function favoriteBikes(): BelongsToMany
    {
        return $this->belongsToMany(Bike::class, 'favorites')->withTimestamps();
    }

    public function activeRide(): ?Ride
    {
        return $this->rides()->where('status', 'ACTIVE')->latest()->first();
    }

    public function isOwnerMode(): bool
    {
        return $this->account_mode === 'OWNER';
    }
}
