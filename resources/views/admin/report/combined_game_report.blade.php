@extends('layouts.master')

@section('style')
<style>
.digital-clock {
    font-family: 'Courier New', Courier, monospace;
    min-width: 160px;
    text-align: center;
    background: #222;
    border: 2px solid #007bff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="row mb-3">
                <div class="col-12">
                    <div id="digitalClock" class="digital-clock bg-dark text-white rounded px-3 py-2 d-inline-block shadow-sm" style="font-size:1.5rem; letter-spacing:2px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow rounded">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Combined Game Report (PlaceBet + Buffalo + PoneWine)</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="form-row align-items-end">
                            <div class="col-md-5">
                                <label for="start_date">Start Date</label>
                                <input type="date" class="form-control" name="start_date" id="start_date" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-5">
                                <label for="end_date">End Date</label>
                                <input type="date" class="form-control" name="end_date" id="end_date" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Combined Report Summary -->
   <div class="row mb-3 justify-content-center">
        <div class="col-12 col-lg-11 col-xl-10">
            
        </div>
    </div>

    <!-- PlaceBet Data -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow rounded">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-dice"></i> GSC Slot Game Report (SETTLED)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Player ID</th>
                                    <th>Agent Name</th>
                                    <th class="text-right">Total Bet</th>
                                    <th class="text-right">Total Win</th>
                                    <th class="text-right">Total Lose</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($placeBetData as $row)
                                    <tr>
                                        <td><strong>{{ $row->member_account }}</strong></td>
                                        <td>{{ $row->agent_name ?? 'N/A' }}</td>
                                        <td class="text-right">{{ number_format($row->total_bet ?? 0, 2) }}</td>
                                        <td class="text-right">{{ number_format($row->total_win ?? 0, 2) }}</td>
                                        <td class="text-right">{{ number_format($row->total_lose ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3">
                                            <small class="text-muted">No PlaceBet data found</small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Buffalo Data -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow rounded">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-gamepad"></i> Buffalo Game Report (Completed)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Player ID</th>
                                    <th>Agent Name</th>
                                    <th class="text-right">Total Bet</th>
                                    <th class="text-right">Total Win</th>
                                    <th class="text-right">Total Lose</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($buffaloBetData as $row)
                                    <tr>
                                        <td><strong>{{ $row->member_account }}</strong></td>
                                        <td>{{ $row->agent_name ?? 'N/A' }}</td>
                                        <td class="text-right">{{ number_format($row->total_bet ?? 0, 2) }}</td>
                                        <td class="text-right">{{ number_format($row->total_win ?? 0, 2) }}</td>
                                        <td class="text-right">{{ number_format($row->total_lose ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3">
                                            <small class="text-muted">No Buffalo data found</small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PoneWine Data -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow rounded">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-wine-glass"></i> PoneWine Total Report (Processed)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Player ID</th>
                                    <th>Agent Name</th>
                                    <th class="text-right">Total Bet</th>
                                    <th class="text-right">Total Win</th>
                                    <th class="text-right">Total Lose</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($poneWineData as $row)
                                    <tr>
                                        <td><strong>{{ $row->member_account }}</strong></td>
                                        <td>{{ $row->agent_name ?? 'N/A' }}</td>
                                        <td class="text-right">{{ number_format($row->total_bet ?? 0, 2) }}</td>
                                        <td class="text-right">{{ number_format($row->total_win ?? 0, 2) }}</td>
                                        <td class="text-right">{{ number_format($row->total_lose ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3">
                                            <small class="text-muted">No PoneWine data found</small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3 justify-content-center">
        <div class="col-12 col-lg-8 col-xl-6">
            
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow rounded">
                <div class="card-body">
                    <h6 class="text-muted mb-3"><i class="fas fa-info-circle"></i> Report Information</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <small class="text-muted">
                                <strong>PlaceBet:</strong> SETTLED wagers only<br>
                                <strong>Buffalo:</strong> Completed status only<br>
                                <strong>PoneWine:</strong> Processed transactions only
                            </small>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">
                                <strong>Date Range:</strong> {{ $startDate }} to {{ $endDate }}<br>
                                <strong>Total Players:</strong> {{ $report->count() }}
                            </small>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">
                                <strong>Report Generated:</strong> {{ now()->format('Y-m-d H:i:s') }}<br>
                                <strong>User:</strong> {{ Auth::user()->user_name }}
                            </small>
                        </div>
                    </div>
                    @if(isset($debugInfo))
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-bug"></i> Debug Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small>
                                            <strong>User Type:</strong> {{ $debugInfo['user_type'] }} ({{ $debugInfo['user_id'] }})<br>
                                            <strong>Date Range:</strong> {{ $debugInfo['start_date'] }} to {{ $debugInfo['end_date'] }}<br>
                                            <strong>Player IDs Found:</strong> {{ $debugInfo['player_ids_count'] }}
                                            @if($debugInfo['player_ids_count'] > 0)
                                                <br><strong>Sample IDs:</strong> {{ implode(', ', $debugInfo['player_ids_sample']) }}
                                            @endif
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <small>
                                            <strong>PlaceBet:</strong> {{ $debugInfo['place_bet_count'] }} aggregated | {{ $debugInfo['place_bet_raw_count'] ?? 0 }} raw records<br>
                                            <strong>Buffalo:</strong> {{ $debugInfo['buffalo_bet_count'] }} aggregated | {{ $debugInfo['buffalo_bet_raw_count'] ?? 0 }} raw records<br>
                                            <strong>PoneWine:</strong> {{ $debugInfo['pone_wine_count'] }} aggregated | {{ $debugInfo['pone_wine_raw_count'] ?? 0 }} raw records
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div> 
@endsection

@section('script')
<script>
function updateClock() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('digitalClock').textContent = `${h}:${m}:${s}`;
}
setInterval(updateClock, 1000);
updateClock();
</script>
@endsection

