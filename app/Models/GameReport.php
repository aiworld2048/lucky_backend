<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent_id',
        'provider_name',
        'game_type',
        'wager_code',
        'bet_amount',
        'prize_amount',
        'net_amount',
        'before_balance',
        'after_balance'
    ];

    public function scopeFilter($query,$filter) {
        $query->when(!empty($filter['search']),function($query,$search) {
            $query->where(function($query) use($search) {
                $joins = collect($query->getQuery()->joins)->pluck('table')->toArray();
                    if (in_array('users as p', $joins)) {
                        $query->orWhere('p.user_name', 'LIKE', "%{$search}%");
                    }
                    if (in_array('users as a', $joins)) {
                        $query->orWhere('a.user_name', 'LIKE', "%{$search}%");
                    }
                    if (in_array('users as m', $joins)) {
                        $query->orWhere('m.user_name', 'LIKE', "%{$search}%");
                    }
            });
        });

        $query->when(!empty($filter['game_type']),function($query,$gameType) {
            $query->where(function($query) use($gameType) {
                $query->where('game_reports.game_type', $gameType);
            });
        });
    }
}
