<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FugoGameList extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fugo_provider_id',
        'name',
        'order',
        'status',
        'hot_status',
        'image',
        'type',
        'provider',
        'gameId',
        'roomId',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'gameId' => 'integer',
        'roomId' => 'integer',
    ];

    /**
     * Get the provider that owns this game list item
     */
    public function fugoProvider(): BelongsTo
    {
        return $this->belongsTo(FugoProvider::class, 'fugo_provider_id');
    }

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
}
