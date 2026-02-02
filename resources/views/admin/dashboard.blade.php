@extends('layouts.master')

@section('content')
<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard</h1>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">

            <!-- User Balance (Owner/Agent/Player) -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($user->wallet->balanceFloat ?? 0, 2) }}</h3>
                        <p>
                            @if($role === 'Owner')
                                Owner Balance
                            @elseif($role === 'Agent')
                                Agent Balance
                            @else
                                Player Balance
                            @endif
                        </p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>

            

            <!-- Player Balance -->
            @if($playerBalance > 0)
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ number_format($playerBalance, 2) }}</h3>
                        <p>Player Balance</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-gamepad"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- User Counts -->
            @if($totalOwner)
            <div class="col-lg-3 col-6">
                <div class="small-box bg-maroon">
                    <div class="inner">
                        <h3>{{ $totalOwner }}</h3>
                        <p>Owners</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
            </div>
            @endif

            @if($totalAgent)
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $totalAgent }}</h3>
                        <p>Agents</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-secret"></i>
                    </div>
                </div>
            </div>
            @endif

            @if($totalPlayer)
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $totalPlayer }}</h3>
                        <p>Players</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
            @endif

           

            <!-- Owner Balance Top-Up -->
            

        </div>
    </div>
</section>
@endsection
