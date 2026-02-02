@extends('layouts.master')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Wager Details</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.wager-list.index') }}">Wager List</a></li>
                        <li class="breadcrumb-item active">Wager Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Wager Information</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.wager-list.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th style="width: 200px;">Wager ID</th>
                                                <td>{{ $wager['id'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Wager Code</th>
                                                <td>
                                                    <code>{{ $wager['code'] ?? 'N/A' }}</code>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Member Account</th>
                                                <td>
                                                    <strong>{{ $wager['member_account'] ?? 'N/A' }}</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Round ID</th>
                                                <td>{{ $wager['round_id'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Currency</th>
                                                <td>
                                                    <span class="badge badge-primary">{{ $wager['currency'] ?? 'N/A' }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Game Type</th>
                                                <td>
                                                    <span class="badge badge-info">{{ $wager['game_type'] ?? 'N/A' }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Game Code</th>
                                                <td>{{ $wager['game_code'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Status</th>
                                                <td>
                                                    @php
                                                        $status = $wager['status'] ?? 'N/A';
                                                        $badgeClass = match($status) {
                                                            'BET' => 'badge-warning',
                                                            'WIN' => 'badge-success',
                                                            'LOSE' => 'badge-danger',
                                                            'SETTLED' => 'badge-info',
                                                            'CANCEL' => 'badge-secondary',
                                                            default => 'badge-secondary'
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }}">{{ $status }}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th style="width: 200px;">Bet Amount</th>
                                                <td class="text-right">
                                                    <strong class="text-primary">{{ number_format($wager['bet_amount'] ?? 0, 2) }}</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Valid Bet Amount</th>
                                                <td class="text-right">
                                                    {{ number_format($wager['valid_bet_amount'] ?? 0, 2) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Prize Amount</th>
                                                <td class="text-right">
                                                    <strong class="text-success">{{ number_format($wager['prize_amount'] ?? 0, 2) }}</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Provider ID</th>
                                                <td>{{ $wager['provider_id'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Provider Line ID</th>
                                                <td>{{ $wager['provider_line_id'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Provider Product ID</th>
                                                <td>{{ $wager['provider_product_id'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Provider Product OID</th>
                                                <td>{{ $wager['provider_product_oid'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Created At</th>
                                                <td>
                                                    @if(isset($wager['created_at']))
                                                        {{ date('Y-m-d H:i:s', $wager['created_at'] / 1000) }}
                                                        <br>
                                                        <small class="text-muted">({{ date('T', $wager['created_at'] / 1000) }})</small>
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Updated At</th>
                                                <td>
                                                    @if(isset($wager['updated_at']))
                                                        {{ date('Y-m-d H:i:s', $wager['updated_at'] / 1000) }}
                                                        <br>
                                                        <small class="text-muted">({{ date('T', $wager['updated_at'] / 1000) }})</small>
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Settled At</th>
                                                <td>
                                                    @if(isset($wager['settled_at']) && $wager['settled_at'] > 0)
                                                        {{ date('Y-m-d H:i:s', $wager['settled_at'] / 1000) }}
                                                        <br>
                                                        <small class="text-muted">({{ date('T', $wager['settled_at'] / 1000) }})</small>
                                                    @else
                                                        <span class="text-muted">Not settled</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            @if(isset($wager['payload']) && $wager['payload'] !== null)
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card card-secondary">
                                            <div class="card-header">
                                                <h3 class="card-title">Payload Data</h3>
                                            </div>
                                            <div class="card-body">
                                                <pre class="bg-light p-3 rounded">{{ json_encode($wager['payload'], JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card card-info">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-info-circle mr-2"></i>API Information
                                            </h3>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Wager ID/Code Used:</strong> <code>{{ $idOrCode }}</code></p>
                                            <p><strong>API Endpoint:</strong> <code>/api/operators/wagers/{{ $idOrCode }}</code></p>
                                            @if(isset($wager['code']))
                                                <div class="mt-3">
                                                    <a href="{{ route('admin.wager-list.game-history', $wager['code']) }}" 
                                                       class="btn btn-primary" 
                                                       target="_blank">
                                                        <i class="fas fa-history mr-2"></i>View Game History
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

