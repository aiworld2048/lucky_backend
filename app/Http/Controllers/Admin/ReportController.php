<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Enums\UserType;
use App\Models\Product;
use App\Models\PlaceBet;
use Illuminate\Http\Request;
use App\Models\LogBuffaloBet;
use Illuminate\Support\Facades\DB;
use App\Models\PoneWineTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin\ReportTransaction;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $agent = Auth::user();
        // $agent = $this->getAgent() ?? Auth::user();

        $report = $this->buildQuery($request, $agent);

        $totalstake = $report->sum('stake_count');
        $totalBetAmt = $report->sum('total_bet');
        $totalWinAmt = $report->sum('total_win');

        $total = [
            'totalstake' => $totalstake,
            'totalBetAmt' => $totalBetAmt,
            'totalWinAmt' => $totalWinAmt,
        ];

        return view('admin.report.index', compact('report', 'total'));
    }

    public function getReportDetails(Request $request, $member_account)
    {
        $player = User::where('user_name', $member_account)->first();
        if (! $player) {
            abort(404, 'Player not found');
        }

        $details = $this->getPlayerDetails($member_account, $request);
        $productTypes = Product::where('status', 1)->get();

        return view('admin.report.detail', compact('details', 'productTypes', 'member_account'));
    }

    private function getAgent()
    {
        $user = Auth::user();

        return $user;
    }

    private function buildQuery(Request $request, $agent)
    {
        $startDate = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

        // Subquery for latest SETTLED per member_account, round_id
        $latestSettledIds = PlaceBet::select(DB::raw('MAX(id) as id'))
            ->where('wager_status', 'SETTLED')
            ->groupBy('member_account', 'round_id');

        $query = PlaceBet::query()
            ->select(
                'place_bets.member_account',
                'agent_user.user_name as agent_name',
                'agent_user.name as name',
                'player_user.name as player_name',
                DB::raw('COUNT(*) as stake_count'),
                DB::raw("SUM(CASE WHEN place_bets.currency = 'MMK2' THEN COALESCE(bet_amount, amount, 0) * 1000 ELSE COALESCE(bet_amount, amount, 0) END) as total_bet"),
                DB::raw("SUM(CASE WHEN place_bets.currency = 'MMK2' THEN prize_amount * 1000 ELSE prize_amount END) as total_win")
            )
            ->leftJoin('users as player_user','place_bets.player_id','=','player_user.id')
            ->leftJoin('users as agent_user', 'place_bets.player_agent_id', '=', 'agent_user.id')
            ->whereIn('place_bets.id', $latestSettledIds)
            ->whereBetween('place_bets.created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59']);

        $playerIds = $this->playerIdsForReport($agent, $request);

        if ($playerIds->isEmpty()) {
            return collect();
        }

        $query->whereIn('place_bets.player_id', $playerIds);

        if ($request->filled('member_account')) {
            $query->where('member_account', $request->member_account);
        }

        return $query->groupBy('place_bets.member_account', 'agent_user.user_name','player_user.name','agent_user.name')->get();
    }


    private function playerIdsForReport(User $user, Request $request): Collection
    {
        $userType = UserType::from((int) $user->type);

        return match ($userType) {
            UserType::Owner => $this->playersForOwner($user, $request->integer('agent_id')),
            UserType::Agent => $this->playersForAgent($user),
            UserType::Player => collect([$user->id]),
            UserType::SystemWallet => User::where('type', UserType::Player->value)->pluck('id'),
        };
    }

    private function getPlayerDetails($member_account, $request)
    {
        $startDate = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

        return PlaceBet::where('member_account', $member_account)
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function show(Request $request, $member_account)
    {
        $query = PlaceBet::query()->where('member_account', $member_account);

        // Get the player user record
        $player = User::where('user_name', $member_account)->first();
        if (! $player) {
            abort(404, 'Player not found');
        }

        // $bets = $query->orderByDesc('created_at')->paginate(50);
        $sub = PlaceBet::selectRaw('MAX(id) as id')
            ->where('member_account', $member_account)
            ->where('wager_status', 'SETTLED')
            ->groupBy('round_id');

        $bets = PlaceBet::whereIn('id', $sub)->orderByDesc('created_at')->paginate(50);

        return view('admin.report.show', compact('bets', 'member_account'));
    }

    public function dailyWinLossReport(Request $request)
    {
        $agent = Auth::user();
        $playerIds = $this->resolvePlayerIds($agent);

        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();

        if ($playerIds->isEmpty()) {
            return view('admin.reports.daily_win_loss', [
                'dailyReports' => collect(),
                'date' => $date,
                'totalTurnover' => 0,
                'totalPayout' => 0,
                'totalWinLoss' => 0,
            ]);
        }

        // 1. Deduplicate: Get only the latest SETTLED per player/round_id for the day
        $ids = PlaceBet::select(DB::raw('MAX(id) as id'))
            ->whereIn('player_id', $playerIds)
            ->where('wager_status', 'SETTLED')
            ->whereDate('created_at', $date)
            ->groupBy('player_id', 'round_id')
            ->pluck('id');

        // 2. Aggregate on those unique records
        // $dailyReports = PlaceBet::whereIn('id', $ids)
        //     ->join('users', 'place_bets.player_id', '=', 'users.id')
        //     ->select(
        //         'users.user_name',
        //         'place_bets.player_id',
        //         DB::raw('SUM(CASE WHEN place_bets.currency = \'MMK2\' THEN place_bets.bet_amount * 1000 ELSE place_bets.bet_amount END) as total_turnover'),
        //         DB::raw('SUM(CASE WHEN place_bets.currency = \'MMK2\' THEN place_bets.prize_amount * 1000 ELSE place_bets.prize_amount END) as total_payout')
        //     )
        //     ->groupBy('users.user_name', 'place_bets.player_id')
        //     ->get();

        $dailyReports = PlaceBet::whereIn('place_bets.id', $ids)
            ->join('users', 'place_bets.player_id', '=', 'users.id')
            ->select(
                'users.user_name',
                'place_bets.player_id',
                DB::raw('SUM(CASE WHEN place_bets.currency = \'MMK2\' THEN place_bets.bet_amount * 1000 ELSE place_bets.bet_amount END) as total_turnover'),
                DB::raw('SUM(CASE WHEN place_bets.currency = \'MMK2\' THEN place_bets.prize_amount * 1000 ELSE place_bets.prize_amount END) as total_payout')
            )
            ->groupBy('users.user_name', 'place_bets.player_id')
            ->get();

        $totalTurnover = $dailyReports->sum('total_turnover');
        $totalPayout = $dailyReports->sum('total_payout');
        $totalWinLoss = $totalPayout - $totalTurnover;

        return view('admin.reports.daily_win_loss', compact('dailyReports', 'date', 'totalTurnover', 'totalPayout', 'totalWinLoss'));
    }

    

    public function gameLogReport(Request $request)
    {
        $agent = Auth::user();
        $playerIds = $this->resolvePlayerIds($agent);

        if ($playerIds->isEmpty()) {
            $fromPlaceholder = $request->from ?? Carbon::today()->toDateString();
            $toPlaceholder = $request->to ?? Carbon::today()->toDateString();

            return view('admin.report.game_log_report', [
                'gameLogs' => collect(),
                'from' => $fromPlaceholder,
                'to' => $toPlaceholder,
            ]);
        }

        $query = PlaceBet::whereIn('player_id', $playerIds)
            ->where('wager_status', 'SETTLED')
            ->select(
                'game_name',
                DB::raw('COUNT(*) as spin_count'),
                DB::raw('SUM(bet_amount) as turnover'),
                DB::raw('SUM(prize_amount) - SUM(bet_amount) as win_loss')
            )
            ->groupBy('game_name')
            ->orderBy('game_name');

        if ($request->has('from') && $request->has('to')) {
            $from = Carbon::parse($request->from)->startOfDay();
            $to = Carbon::parse($request->to)->endOfDay();
            $query->whereBetween('created_at', [$from, $to]);
        } else {
            // Default to today
            $query->whereDate('created_at', Carbon::today());
        }

        $gameLogs = $query->get();
        $from = $request->from ?? Carbon::today()->toDateString();
        $to = $request->to ?? Carbon::today()->toDateString();

        return view('admin.report.game_log_report', compact('gameLogs', 'from', 'to'));
    }

    private function resolvePlayerIds(User $user): Collection
    {
        $userType = UserType::from((int) $user->type);

        return match ($userType) {
            UserType::Owner => $this->playersForOwner($user),
            UserType::Agent => $this->playersForAgent($user),
            UserType::Player => collect([$user->id]),
            UserType::SystemWallet => User::where('type', UserType::Player->value)->pluck('id'),
        };
    }

    private function playersForOwner(User $owner, ?int $agentFilterId = null): Collection
    {
        $agentIds = User::where('agent_id', $owner->id)
            ->where('type', UserType::Agent->value)
            ->pluck('id');

        if ($agentFilterId && $agentIds->contains($agentFilterId)) {
            $agentIds = collect([$agentFilterId]);
        }

        if ($agentIds->isEmpty()) {
            return collect();
        }

        return User::where('type', UserType::Player->value)
            ->whereIn('agent_id', $agentIds)
            ->pluck('id');
    }

    private function playersForAgent(User $agent): Collection
    {
        return User::where('agent_id', $agent->id)
            ->where('type', UserType::Player->value)
            ->pluck('id');
    }

    public function getReport(Request $request,$playerId)
    {
        $fromDate = $request->input('from_date', now()->format('Y-m-d'));
        $toDate = $request->input('to_date', now()->format('Y-m-d'));

        $poneWines = PoneWineTransaction::where('player_id',$playerId)
                                        ->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);

        $poneWineSummary = (clone $poneWines)
                            ->selectRaw('SUM(bet_amount) as total_bet, SUM(win_lose_amount) as total_win')
                            ->first();

        $poneWinesBetAmount =  $poneWineSummary->total_bet ?? 0;
        $poneWinesWinAmount = $poneWineSummary->total_win ?? 0;
        $poneWinesNetWin    = $poneWinesWinAmount - $poneWinesBetAmount;
        // $poneWines = $poneWines->paginate(10)->setPageName('pone_wine_page');
        $poneWines = $poneWines->orderBy('created_at', 'desc')->paginate(10, ['*'], 'pone_wine_page', $request->input('pone_wine_page'));

        $shans     = ReportTransaction::where('user_id',$playerId)
                                        ->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);

        $shanSummary = (clone $shans)
                        ->selectRaw('SUM(bet_amount) as total_bet, SUM(valid_amount) as total_win')
                        ->first();

        $shansBetAmount = $shanSummary->total_bet ?? 0;
        $shansWinAmount = $shanSummary->total_win ?? 0;
        $shansNetWin    = $shansWinAmount - $shansBetAmount;
        // $shans = $shans->paginate(10)->setPageName('shan_page');
        $shans = $shans->orderBy('created_at', 'desc')->paginate(10, ['*'], 'shan_page', $request->input('shan_page'));

        $buffalos =  LogBuffaloBet::where('player_id',$playerId)
                                   ->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);

        $buffaloSummary = (clone $buffalos)
                            ->selectRaw('SUM(bet_amount) as total_bet, SUM(win_amount) as total_win')
                            ->first();
        $buffalosBetAmount = $buffaloSummary->total_bet ?? 0;
        $buffalosWinAmount = $buffaloSummary->total_win ?? 0;
        $buffalosNetWin    = $buffalosWinAmount-$buffalosBetAmount;
        // $buffalos = $buffalos->paginate(10)->setPageName('buffalo_page');
        $buffalos = $buffalos->orderBy('created_at', 'desc')->paginate(10, ['*'], 'buffalo_page', $request->input('buffalo_page'));

        $slots    =  PlaceBet::where('player_id',$playerId)
                                ->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);

        $slotSummary = (clone $slots)
                        ->selectRaw('SUM(bet_amount) as total_bet, SUM(valid_bet_amount) as total_win')
                        ->first();

        $slotsBetAmount = $slotSummary->total_bet ?? 0;
        $slotsWinAmount = $slotSummary->total_win ?? 0;
        $slotsNetWin    = $slotsWinAmount-$slotsBetAmount;
        // $slots = $slots->paginate(10)->setPageName('slot_page');
        $slots = $slots->orderBy('created_at', 'desc')->paginate(10, ['*'], 'slot_page', $request->input('slot_page'));

        $totalNetWin = $slotsNetWin + $buffalosNetWin + $poneWinesNetWin + $shansNetWin ;
        $totalBetAmount = $slotsBetAmount + $buffalosBetAmount + $poneWinesBetAmount + $shansBetAmount ;
        $totalWinAmount = $slotsWinAmount + $buffalosWinAmount + $poneWinesWinAmount + $shansWinAmount ;

        $data = [
        'ponewine' => [
            'bet' => $poneWinesBetAmount,
            'win' => $poneWinesWinAmount,
            'net' => $poneWinesNetWin,
        ],
        'shan' => [
            'bet' => $shansBetAmount,
            'win' => $shansWinAmount,
            'net' => $shansNetWin,
        ],
        'buffalo' => [
            'bet' => $buffalosBetAmount,
            'win' => $buffalosWinAmount,
            'net' => $buffalosNetWin,
        ],
        'slot' => [
            'bet' => $slotsBetAmount,
            'win' => $slotsWinAmount,
            'net' => $slotsNetWin,
        ],
        'total' => [
            'totalNetWin' => $totalNetWin,
            'totalBetAmount' => $totalBetAmount,
            'totalWinAmount' => $totalWinAmount
        ]
        ];

        return view('agent.player.report_index',compact(
            'poneWines',
            'shans',
            'buffalos',
            'slots',
            'playerId',
            'data'
        ));
    }

    /**
     * Sub-Agent Commission Report grouped by player_reg_player_ref_code
     * Visible to Owner, Agent, and SubAgent
     * 
     * For SubAgent: Only shows players who registered with their referral code
     * For Owner/Agent: Shows all sub-agent commission reports
     */
    public function subAgentCommissionReport(Request $request)
    {
        $agent = Auth::user();
        $userType = UserType::from((int) $agent->type);

        // Only Owner, Agent, and SubAgent can access this report
        if (!in_array($userType, [UserType::Owner, UserType::Agent, UserType::SubAgent])) {
            abort(403, 'Unauthorized access');
        }

        $start_date = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
        $end_date = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

        // For SubAgent: Only show their own referral code data
        // For Owner/Agent: Show all data based on their player hierarchy
        if ($userType === UserType::SubAgent) {
            // Sub-agent can only see players registered with their referral code
            if (!$agent->referral_code) {
                return view('admin.report.subagent_commission', [
                    'reports' => collect(),
                    'total' => [
                        'total_stake' => 0,
                        'total_bet' => 0,
                        'total_win' => 0,
                        'total_net' => 0,
                    ],
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                ]);
            }

            // Get player IDs where reg_player_ref_code matches sub-agent's referral_code
            $playerIds = User::where('type', UserType::Player->value)
                ->where('reg_player_ref_code', $agent->referral_code)
                ->pluck('id');

            if ($playerIds->isEmpty()) {
                return view('admin.report.subagent_commission', [
                    'reports' => collect(),
                    'total' => [
                        'total_stake' => 0,
                        'total_bet' => 0,
                        'total_win' => 0,
                        'total_net' => 0,
                    ],
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                ]);
            }

            // Subquery for latest SETTLED per member_account, round_id
            // Filter by sub-agent's referral code
            $latestSettledIds = PlaceBet::select(DB::raw('MAX(id) as id'))
                ->where('wager_status', 'SETTLED')
                ->whereIn('player_id', $playerIds)
                ->where('player_reg_player_ref_code', $agent->referral_code) // Only this sub-agent's ref code
                ->whereNotNull('player_reg_player_ref_code')
                ->whereBetween('created_at', [$start_date.' 00:00:00', $end_date.' 23:59:59'])
                ->groupBy('member_account', 'round_id')
                ->pluck('id');

            // Build query - SubAgent only sees their own data
            $query = PlaceBet::query()
                ->select(
                    'place_bets.player_reg_player_ref_code',
                    'subagent_user.user_name as subagent_user_name',
                    'subagent_user.name as subagent_name',
                    'subagent_user.id as subagent_id',
                    'main_agent_user.user_name as main_agent_user_name',
                    'main_agent_user.name as main_agent_name',
                    DB::raw('COUNT(DISTINCT place_bets.player_id) as total_players'),
                    DB::raw('COUNT(*) as stake_count'),
                    DB::raw("SUM(CASE WHEN place_bets.currency = 'MMK2' THEN COALESCE(place_bets.bet_amount, place_bets.amount, 0) * 1000 ELSE COALESCE(place_bets.bet_amount, place_bets.amount, 0) END) as total_bet"),
                    DB::raw("SUM(CASE WHEN place_bets.currency = 'MMK2' THEN place_bets.prize_amount * 1000 ELSE place_bets.prize_amount END) as total_win"),
                    DB::raw('COALESCE(MAX(subagent_user.commission), 0) as subagent_commission_pct'),
                    DB::raw('COALESCE(MAX(subagent_user.commission), 0)/100 * ( SUM(CASE WHEN place_bets.currency = \'MMK2\' THEN COALESCE(place_bets.bet_amount, place_bets.amount, 0) * 1000 ELSE COALESCE(place_bets.bet_amount, place_bets.amount, 0) END) - SUM(CASE WHEN place_bets.currency = \'MMK2\' THEN place_bets.prize_amount * 1000 ELSE place_bets.prize_amount END) ) as subagent_commission_amount')
                )
                ->leftJoin('users as player_user', 'place_bets.player_id', '=', 'player_user.id')
                ->leftJoin('users as subagent_user', function($join) use ($agent) {
                    $join->on('place_bets.player_reg_player_ref_code', '=', 'subagent_user.referral_code')
                         ->where('subagent_user.type', '=', UserType::SubAgent->value)
                         ->where('subagent_user.referral_code', '=', $agent->referral_code); // Only this sub-agent
                })
                ->leftJoin('users as main_agent_user', function($join) {
                    $join->on('player_user.agent_id', '=', 'main_agent_user.id')
                         ->where('main_agent_user.type', '=', UserType::Agent->value);
                })
                ->whereIn('place_bets.id', $latestSettledIds)
                ->where('place_bets.player_reg_player_ref_code', $agent->referral_code) // Filter by sub-agent's ref code
                ->whereIn('place_bets.player_id', $playerIds);

        } else {
            // Owner/Agent: Show all sub-agent commission reports
            $playerIds = $this->playerIdsForReport($agent, $request);

            if ($playerIds->isEmpty()) {
                return view('admin.report.subagent_commission', [
                    'reports' => collect(),
                    'total' => [
                        'total_stake' => 0,
                        'total_bet' => 0,
                        'total_win' => 0,
                        'total_net' => 0,
                    ],
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                ]);
            }

            // Subquery for latest SETTLED per member_account, round_id
            // Optimized: Filter by player_ids early to reduce subquery size
            $latestSettledIds = PlaceBet::select(DB::raw('MAX(id) as id'))
                ->where('wager_status', 'SETTLED')
                ->whereIn('player_id', $playerIds)
                ->whereNotNull('player_reg_player_ref_code')
                ->whereBetween('created_at', [$start_date.' 00:00:00', $end_date.' 23:59:59'])
                ->groupBy('member_account', 'round_id')
                ->pluck('id');

            // Build query grouped by player_reg_player_ref_code
            // NO N+1 QUERIES: All data fetched in single query using LEFT JOINs
            // - Sub-agent info joined via referral_code
            // - Main agent info joined via player's agent_id
            // - All aggregations done in SQL, not in PHP
            $query = PlaceBet::query()
                ->select(
                    'place_bets.player_reg_player_ref_code',
                    'subagent_user.user_name as subagent_user_name',
                    'subagent_user.name as subagent_name',
                    'subagent_user.id as subagent_id',
                    'main_agent_user.user_name as main_agent_user_name',
                    'main_agent_user.name as main_agent_name',
                    DB::raw('COUNT(DISTINCT place_bets.player_id) as total_players'),
                    DB::raw('COUNT(*) as stake_count'),
                    DB::raw("SUM(CASE WHEN place_bets.currency = 'MMK2' THEN COALESCE(place_bets.bet_amount, place_bets.amount, 0) * 1000 ELSE COALESCE(place_bets.bet_amount, place_bets.amount, 0) END) as total_bet"),
                    DB::raw("SUM(CASE WHEN place_bets.currency = 'MMK2' THEN place_bets.prize_amount * 1000 ELSE place_bets.prize_amount END) as total_win"),
                    DB::raw('COALESCE(MAX(subagent_user.commission), 0) as subagent_commission_pct'),
                    DB::raw('COALESCE(MAX(subagent_user.commission), 0)/100 * ( SUM(CASE WHEN place_bets.currency = \'MMK2\' THEN COALESCE(place_bets.bet_amount, place_bets.amount, 0) * 1000 ELSE COALESCE(place_bets.bet_amount, place_bets.amount, 0) END) - SUM(CASE WHEN place_bets.currency = \'MMK2\' THEN place_bets.prize_amount * 1000 ELSE place_bets.prize_amount END) ) as subagent_commission_amount')
                )
                ->leftJoin('users as player_user', 'place_bets.player_id', '=', 'player_user.id')
                ->leftJoin('users as subagent_user', function($join) {
                    $join->on('place_bets.player_reg_player_ref_code', '=', 'subagent_user.referral_code')
                         ->where('subagent_user.type', '=', UserType::SubAgent->value);
                })
                ->leftJoin('users as main_agent_user', function($join) {
                    $join->on('player_user.agent_id', '=', 'main_agent_user.id')
                         ->where('main_agent_user.type', '=', UserType::Agent->value);
                })
                ->whereIn('place_bets.id', $latestSettledIds)
                ->whereNotNull('place_bets.player_reg_player_ref_code')
                ->whereIn('place_bets.player_id', $playerIds);

            // Filter by sub-agent referral code if provided (for Owner/Agent only)
            if ($request->filled('subagent_ref_code')) {
                $query->where('place_bets.player_reg_player_ref_code', $request->subagent_ref_code);
            }
        }

        $reports = $query->groupBy(
            'place_bets.player_reg_player_ref_code',
            'subagent_user.user_name',
            'subagent_user.name',
            'subagent_user.id',
            'main_agent_user.user_name',
            'main_agent_user.name'
        )->get();

        // Calculate totals
        $total = [
            'total_stake' => $reports->sum('stake_count'),
            'total_bet' => $reports->sum('total_bet'),
            'total_win' => $reports->sum('total_win'),
            'total_net' => $reports->sum('total_win') - $reports->sum('total_bet'),
            'total_commission' => $reports->sum('subagent_commission_amount'),
        ];

        return view('admin.report.subagent_commission', compact('reports', 'total', 'start_date', 'end_date'));
    }
}
