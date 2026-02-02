<?php

namespace App\Http\Controllers\Admin\BuffaloGame;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\LogBuffaloBet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BuffaloSubAgentCommissionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $userType = UserType::from((int) $user->type);

        if (! in_array($userType, [UserType::Owner, UserType::Agent, UserType::SubAgent])) {
            abort(403, 'Unauthorized access');
        }

        $start_date = $request->start_date ?? Carbon::today()->toDateString();
        $end_date = $request->end_date ?? Carbon::today()->toDateString();

        $baseQuery = LogBuffaloBet::query()
            ->whereNotNull('player_reg_player_ref_code')
            ->whereBetween('log_buffalo_bets.created_at', [$start_date.' 00:00:00', $end_date.' 23:59:59']);

        // Build scoped query based on role
        if ($userType === UserType::SubAgent) {
            $referralCode = $user->referral_code;
            if (! $referralCode) {
                return $this->emptyResult($start_date, $end_date);
            }

            $playerIds = User::where('type', UserType::Player->value)
                ->where('reg_player_ref_code', $referralCode)
                ->pluck('id');

            if ($playerIds->isEmpty()) {
                return $this->emptyResult($start_date, $end_date);
            }

            $query = (clone $baseQuery)
                ->where('player_reg_player_ref_code', $referralCode)
                ->whereIn('player_id', $playerIds);
        } elseif ($userType === UserType::Agent) {
            $playerIds = $user->getAllDescendantPlayers()->pluck('id');
            if ($playerIds->isEmpty()) {
                return $this->emptyResult($start_date, $end_date);
            }

            $query = (clone $baseQuery)->whereIn('player_id', $playerIds);

            if ($request->filled('subagent_ref_code')) {
                $query->where('player_reg_player_ref_code', $request->subagent_ref_code);
            }
        } else { // Owner
            $query = (clone $baseQuery);

            if ($request->filled('subagent_ref_code')) {
                $query->where('player_reg_player_ref_code', $request->subagent_ref_code);
            }
        }

        $reports = $query
            ->select(
                'log_buffalo_bets.player_reg_player_ref_code',
                'subagent_user.user_name as subagent_user_name',
                'subagent_user.name as subagent_name',
                'subagent_user.id as subagent_id',
                'main_agent_user.user_name as main_agent_user_name',
                'main_agent_user.name as main_agent_name',
                DB::raw('COUNT(DISTINCT log_buffalo_bets.player_id) as total_players'),
                DB::raw('COUNT(*) as stake_count'),
                DB::raw('SUM(log_buffalo_bets.bet_amount) as total_bet'),
                DB::raw('SUM(log_buffalo_bets.win_amount) as total_win'),
                DB::raw('COALESCE(MAX(subagent_user.commission), 0) as subagent_commission_pct'),
                DB::raw("
                    CASE
                        WHEN SUM(log_buffalo_bets.win_amount) < SUM(log_buffalo_bets.bet_amount)
                            THEN (SUM(log_buffalo_bets.bet_amount) - SUM(log_buffalo_bets.win_amount)) * COALESCE(MAX(subagent_user.commission), 0) / 100
                        ELSE 0
                    END as subagent_commission_amount
                ")
            )
            ->leftJoin('users as subagent_user', function ($join) {
                $join->on('log_buffalo_bets.player_reg_player_ref_code', '=', 'subagent_user.referral_code')
                    ->where('subagent_user.type', '=', UserType::SubAgent->value);
            })
            ->leftJoin('users as main_agent_user', function ($join) {
                $join->on('log_buffalo_bets.player_agent_id', '=', 'main_agent_user.id')
                    ->where('main_agent_user.type', '=', UserType::Agent->value);
            })
            ->groupBy(
                'log_buffalo_bets.player_reg_player_ref_code',
                'subagent_user.user_name',
                'subagent_user.name',
                'subagent_user.id',
                'main_agent_user.user_name',
                'main_agent_user.name'
            )
            ->get();

        // Attach players grouped by referral code to avoid N+1 queries
        $refCodes = $reports->pluck('player_reg_player_ref_code')->filter()->unique();
        $playersByRef = User::whereIn('reg_player_ref_code', $refCodes)
            ->where('type', UserType::Player->value)
            ->get(['id', 'name', 'user_name', 'reg_player_ref_code'])
            ->groupBy('reg_player_ref_code');

        $reports->each(function ($report) use ($playersByRef) {
            $report->players = $playersByRef[$report->player_reg_player_ref_code] ?? collect();
        });

        $total = [
            'total_stake' => $reports->sum('stake_count'),
            'total_bet' => $reports->sum('total_bet'),
            'total_win' => $reports->sum('total_win'),
            'total_net' => $reports->sum('total_win') - $reports->sum('total_bet'),
            'total_commission' => $reports->sum('subagent_commission_amount'),
        ];

        return view('admin.report.buffalo_subagent_commission', compact(
            'reports',
            'total',
            'start_date',
            'end_date',
            'userType'
        ));
    }

    private function emptyResult(string $start_date, string $end_date)
    {
        return view('admin.report.buffalo_subagent_commission', [
            'reports' => collect(),
            'total' => [
                'total_stake' => 0,
                'total_bet' => 0,
                'total_win' => 0,
                'total_net' => 0,
                'total_commission' => 0,
            ],
            'start_date' => $start_date,
            'end_date' => $end_date,
            'userType' => null,
        ]);
    }
}

