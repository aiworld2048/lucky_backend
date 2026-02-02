@extends('layouts.master')

@section('style')
<style>
:root {
    --report-bg: #f4f6fb;
    --card-border: #e2e6f0;
    --text-muted: #6c757d;
    --success-soft: rgba(15, 157, 88, 0.12);
    --danger-soft: rgba(217, 48, 37, 0.12);
}
.report-page {
    background: var(--report-bg);
    padding-bottom: 2rem;
}
.page-header {
    background: linear-gradient(135deg, #0d6efd, #6610f2);
    border-radius: 20px;
    padding: 2rem;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.filter-card {
    border: none;
    border-radius: 16px;
}
.filter-card .card-body {
    padding: 1.75rem;
}
.table-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid var(--card-border);
    box-shadow: 0 10px 30px rgba(13, 110, 253, 0.05);
}
.net-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
}
.net-badge--positive {
    background: var(--success-soft);
    color: #0f9d58;
}
.net-badge--negative {
    background: var(--danger-soft);
    color: #d93025;
}
</style>
@endsection

@section('content')
<div class="report-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-12">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Sub-Agent Commission Report</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="page-label text-uppercase mb-2">COMMISSION REPORT</div>
                        <h2 class="mb-0">Sub-Agent Commission Report</h2>
                        <p class="mb-0 mt-2" style="opacity: 0.9;">Grouped by Registration Referral Code</p>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card filter-card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.report.subagent-commission') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                value="{{ $start_date ?? '' }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                value="{{ $end_date ?? '' }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="subagent_ref_code" class="form-label">Sub-Agent Referral Code (Optional)</label>
                            <input type="text" class="form-control" id="subagent_ref_code" name="subagent_ref_code" 
                                value="{{ request('subagent_ref_code') ?? '' }}" placeholder="Filter by referral code">
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('admin.report.subagent-commission') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <h4 class="text-muted mb-1" style="font-size: 0.85rem; font-weight: 500;">Total Stakes</h4>
                        <h3 class="mb-0">{{ number_format($total['total_stake'] ?? 0) }}</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <h4 class="text-muted mb-1" style="font-size: 0.85rem; font-weight: 500;">Total Bet Amount</h4>
                        <h3 class="mb-0">{{ number_format($total['total_bet'] ?? 0, 2) }}</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <h4 class="text-muted mb-1" style="font-size: 0.85rem; font-weight: 500;">Total Win Amount</h4>
                        <h3 class="mb-0">{{ number_format($total['total_win'] ?? 0, 2) }}</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <h4 class="text-muted mb-1" style="font-size: 0.85rem; font-weight: 500;">Net Win/Loss</h4>
                        <h3 class="mb-0 {{ ($total['total_net'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ ($total['total_net'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($total['total_net'] ?? 0, 2) }}
                        </h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <h4 class="text-muted mb-1" style="font-size: 0.85rem; font-weight: 500;">Commission</h4>
                        <h3 class="mb-0 {{ ($total['total_commission'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ ($total['total_commission'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($total['total_commission'] ?? 0, 2) }}
                        </h3>
                    </div>
                </div>
            </div>

            <!-- Report Table -->
            <div class="card table-card">
                <div class="card-header">
                    <h3 class="card-title">Sub-Agent Commission Report</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="mytable" class="table table-bordered table-hover" data-disable-datatable="true">
                            <thead class="text-center" style="background: #f8f9fa;">
                                <tr>
                                    <th>#</th>
                                    <th>Sub-Agent Referral Code</th>
                                    <th>Sub-Agent Name</th>
                                    <th>Sub-Agent ID</th>
                                    <th>Main Agent</th>
                                    <th>Total Players</th>
                                    <th>Total Stakes</th>
                                    <th>Total Bet</th>
                                    <th>Total Win</th>
                                    <th>Net Win/Loss</th>
                                    <th>Commission</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reports as $index => $report)
                                    @php
                                        $netWinLoss = $report->total_win - $report->total_bet;
                                    @endphp
                                    <tr class="text-center">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>{{ $report->player_reg_player_ref_code ?? 'N/A' }}</strong>
                                        </td>
                                        <td>
                                            {{ $report->subagent_name ?? 'N/A' }}
                                        </td>
                                        <td>
                                            {{ $report->subagent_user_name ?? 'N/A' }}
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $report->main_agent_user_name ?? 'N/A' }}</strong>
                                                @if($report->main_agent_name)
                                                    <br><small class="text-muted">{{ $report->main_agent_name }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ number_format($report->total_players ?? 0) }}</td>
                                        <td>{{ number_format($report->stake_count ?? 0) }}</td>
                                        <td class="text-right font-weight-bold">{{ number_format($report->total_bet ?? 0, 2) }}</td>
                                        <td class="text-right font-weight-bold">{{ number_format($report->total_win ?? 0, 2) }}</td>
                                        <td class="text-center">
                                            <span class="net-badge {{ $netWinLoss >= 0 ? 'net-badge--positive' : 'net-badge--negative' }}">
                                                {!! $netWinLoss >= 0 ? '&#9650;' : '&#9660;' !!}
                                                {{ $netWinLoss >= 0 ? '+' : '' }}{{ number_format(abs($netWinLoss), 2) }}
                                            </span>
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
        </div>
    </section>
</div>
@endsection

@section('script')
<script>
    $(function() {
        const $myTable = $('#mytable');
        const datatableDisabled = $myTable.data('disable-datatable');

        // Prevent DataTables double initialization
        if ($myTable.length && !datatableDisabled) {
            // Check if DataTable is already initialized
            if ($.fn.DataTable.isDataTable('#mytable')) {
                $myTable.DataTable().destroy();
            }
            
            $myTable.DataTable({
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "order": [[0, "asc"]],
                "pageLength": 25,
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                }
            });
        }
    });
</script>
@endsection

