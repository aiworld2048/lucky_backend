@extends('layouts.master')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Wallet Balance</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.wager-list.index') }}">Wager List</a></li>
                        <li class="breadcrumb-item active">Wallet Balance</li>
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
                            <h3 class="card-title">Wallet Balance Information</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.wager-list.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                                </a>
                                <button type="button" class="btn btn-sm btn-primary" onclick="location.reload()">
                                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Operator Information -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="info-box bg-info">
                                        <span class="info-box-icon"><i class="fas fa-building"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Operator Code</span>
                                            <span class="info-box-number">{{ $data['operator_code'] ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box {{ ($data['is_credit'] ?? false) ? 'bg-success' : 'bg-warning' }}">
                                        <span class="info-box-icon"><i class="fas fa-{{ ($data['is_credit'] ?? false) ? 'check-circle' : 'exclamation-triangle' }}"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Credit Mode</span>
                                            <span class="info-box-number">
                                                @if($data['is_credit'] ?? false)
                                                    <span class="badge badge-success">Credit Mode</span>
                                                @else
                                                    <span class="badge badge-warning">Buy-in Mode</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Response Status -->
                            <div class="alert alert-{{ $code === 0 ? 'success' : 'danger' }}">
                                <strong>Status:</strong> {{ $message }}
                                @if($code === 0)
                                    <span class="badge badge-success ml-2">Code: {{ $code }}</span>
                                @else
                                    <span class="badge badge-danger ml-2">Code: {{ $code }}</span>
                                @endif
                            </div>

                            <!-- Currencies Table -->
                            @if(isset($data['currencies']) && count($data['currencies']) > 0)
                                <div class="row">
                                    <div class="col-12">
                                        <h4 class="mb-3">
                                            <i class="fas fa-coins mr-2"></i>Currency Balances
                                            <span class="badge badge-info">{{ count($data['currencies']) }} currencies</span>
                                        </h4>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover">
                                                <thead class="thead-dark">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Currency</th>
                                                        <th class="text-right">Current Balance</th>
                                                        <th>Last Updated</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($data['currencies'] as $index => $currency)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>
                                                                <strong class="text-primary">{{ $currency['currency'] ?? 'N/A' }}</strong>
                                                            </td>
                                                            <td class="text-right">
                                                                <strong class="text-success" style="font-size: 1.1em;">
                                                                    {{ number_format($currency['current_balance'] ?? 0, 4) }}
                                                                </strong>
                                                            </td>
                                                            <td>
                                                                @if(isset($currency['updated_at']))
                                                                    @php
                                                                        $updatedAt = is_numeric($currency['updated_at']) 
                                                                            ? ($currency['updated_at'] > 1e12 ? $currency['updated_at'] / 1000 : $currency['updated_at'])
                                                                            : strtotime($currency['updated_at']);
                                                                    @endphp
                                                                    {{ date('Y-m-d H:i:s', $updatedAt) }}
                                                                    <br>
                                                                    <small class="text-muted">{{ date('T', $updatedAt) }}</small>
                                                                @else
                                                                    <span class="text-muted">N/A</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="2" class="text-right">Total Currencies:</th>
                                                        <th class="text-right">{{ count($data['currencies']) }}</th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    No currency balances found.
                                </div>
                            @endif

                            <!-- Summary Cards -->
                            @if(isset($data['currencies']) && count($data['currencies']) > 0)
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h4 class="mb-3">
                                            <i class="fas fa-chart-pie mr-2"></i>Balance Summary
                                        </h4>
                                    </div>
                                    @foreach($data['currencies'] as $currency)
                                        <div class="col-md-3 col-sm-6 mb-3">
                                            <div class="card card-outline card-primary">
                                                <div class="card-header">
                                                    <h3 class="card-title">
                                                        <i class="fas fa-money-bill-wave mr-2"></i>{{ $currency['currency'] ?? 'N/A' }}
                                                    </h3>
                                                </div>
                                                <div class="card-body">
                                                    <h2 class="mb-0 text-primary">
                                                        {{ number_format($currency['current_balance'] ?? 0, 4) }}
                                                    </h2>
                                                    <p class="text-muted mb-0">
                                                        <small>
                                                            Updated: 
                                                            @if(isset($currency['updated_at']))
                                                                @php
                                                                    $updatedAt = is_numeric($currency['updated_at']) 
                                                                        ? ($currency['updated_at'] > 1e12 ? $currency['updated_at'] / 1000 : $currency['updated_at'])
                                                                        : strtotime($currency['updated_at']);
                                                                @endphp
                                                                {{ date('M d, Y H:i', $updatedAt) }}
                                                            @else
                                                                N/A
                                                            @endif
                                                        </small>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <!-- API Information -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card card-info">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-info-circle mr-2"></i>API Information
                                            </h3>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>API Endpoint:</strong> <code>/api/operators/wallet-balance</code></p>
                                            <p><strong>Method:</strong> <code>GET</code></p>
                                            <p><strong>Signature:</strong> <code>md5(request_time + secret_key + "getwalletcurrencies" + operator_code)</code></p>
                                            <p><strong>Request Time:</strong> Timestamp in milliseconds</p>
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

@section('styles')
<style>
    .info-box {
        display: block;
        min-height: 90px;
        background: #fff;
        width: 100%;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        border-radius: 2px;
        margin-bottom: 15px;
    }
    .info-box-icon {
        border-top-left-radius: 2px;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 2px;
        display: block;
        float: left;
        height: 90px;
        width: 90px;
        text-align: center;
        font-size: 45px;
        line-height: 90px;
        background: rgba(0,0,0,0.2);
    }
    .info-box-content {
        padding: 5px 10px;
        margin-left: 90px;
    }
    .info-box-text {
        text-transform: uppercase;
        font-weight: 600;
        font-size: 13px;
    }
    .info-box-number {
        display: block;
        font-weight: bold;
        font-size: 18px;
    }
</style>
@endsection

