@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h4 class="mb-0">Buffalo Sub-Agent Commission Report</h4>
            <small class="text-muted">Grouped by player_reg_player_ref_code</small>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="form-row align-items-end">
                <div class="form-group col-md-3">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $start_date ?? '' }}">
                </div>
                <div class="form-group col-md-3">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $end_date ?? '' }}">
                </div>
                @if($userType !== \App\Enums\UserType::SubAgent)
                    <div class="form-group col-md-3">
                        <label for="subagent_ref_code">Sub-Agent Referral Code</label>
                        <input type="text" id="subagent_ref_code" name="subagent_ref_code" class="form-control" value="{{ request('subagent_ref_code') }}" placeholder="Optional filter">
                    </div>
                @endif
                <div class="form-group col-md-2">
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card stat-card">
                <h6 class="text-muted mb-1">Stakes</h6>
                <h4 class="mb-0">{{ number_format($total['total_stake'] ?? 0) }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <h6 class="text-muted mb-1">Bet</h6>
                <h4 class="mb-0">{{ number_format($total['total_bet'] ?? 0, 2) }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <h6 class="text-muted mb-1">Win</h6>
                <h4 class="mb-0">{{ number_format($total['total_win'] ?? 0, 2) }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <h6 class="text-muted mb-1">Commission</h6>
                <h4 class="mb-0 {{ ($total['total_commission'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ ($total['total_commission'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($total['total_commission'] ?? 0, 2) }}
                </h4>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover" data-disable-datatable="true">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sub-Agent</th>
                        <th>Main Agent</th>
                        <th>Referral Code</th>
                        <th>Players</th>
                        <th>Total Players</th>
                        <th>Stakes</th>
                        <th class="text-right">Bet</th>
                        <th class="text-right">Win</th>
                        <th class="text-right">Net</th>
                        <th class="text-right">Commission</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $index => $report)
                        @php
                            $net = ($report->total_win ?? 0) - ($report->total_bet ?? 0);
                            $players = $report->players ?? collect();
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $report->subagent_user_name ?? '-' }}</strong><br>
                                <small class="text-muted">{{ $report->subagent_name ?? '-' }}</small>
                            </td>
                            <td>
                                <strong>{{ $report->main_agent_user_name ?? '-' }}</strong><br>
                                <small class="text-muted">{{ $report->main_agent_name ?? '-' }}</small>
                            </td>
                            <td>{{ $report->player_reg_player_ref_code ?? '-' }}</td>
                            <td>
                                @if($players->isNotEmpty())
                                    <small>
                                        @foreach($players as $player)
                                            <div>{{ $player->user_name }} <span class="text-muted">({{ $player->name }})</span></div>
                                        @endforeach
                                    </small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ number_format($report->total_players ?? 0) }}</td>
                            <td>{{ number_format($report->stake_count ?? 0) }}</td>
                            <td class="text-right">{{ number_format($report->total_bet ?? 0, 2) }}</td>
                            <td class="text-right">{{ number_format($report->total_win ?? 0, 2) }}</td>
                            <td class="text-right {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $net >= 0 ? '+' : '' }}{{ number_format($net, 2) }}
                            </td>
                            <td class="text-right font-weight-bold">
                                {{ number_format($report->subagent_commission_amount ?? 0, 2) }}
                                @if(isset($report->subagent_commission_pct))
                                    <br><small class="text-muted">({{ number_format($report->subagent_commission_pct ?? 0, 2) }}%)</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-4">
                                <p class="text-muted mb-0">No data found for the selected date range.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

