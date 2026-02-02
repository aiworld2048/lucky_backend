<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FugoProvider extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'image',
        'type',
        'provider',
        'gameId',
        'roomId',
        'jackpot',
        'rtp',
        'BuyFreeSpin',
        'transfer_wallet',
        'seamless',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'gameId' => 'integer',
        'roomId' => 'integer',
        'jackpot' => 'integer',
        'rtp' => 'integer',
        'BuyFreeSpin' => 'integer',
        'transfer_wallet' => 'integer',
        'seamless' => 'integer',
    ];

    /**
     * Scope to filter by game ID
     */
    public function scopeByGameId($query, int $gameId)
    {
        return $query->where('gameId', $gameId);
    }

    /**
     * Scope to filter by room ID
     */
    public function scopeByRoomId($query, int $roomId)
    {
        return $query->where('roomId', $roomId);
    }

    /**
     * Scope to filter by provider
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to filter by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the game lists for this provider
     */
    public function gameLists(): HasMany
    {
        return $this->hasMany(FugoGameList::class, 'fugo_provider_id');
    }
}
