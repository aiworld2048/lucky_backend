@extends('layouts.master')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
                        <li class="breadcrumb-item active">Sub-Agent Profile</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="card col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-10 offset-sm-1" style="border-radius: 15px;">
                <div class="card-header">
                    <div class="card-title col-12">
                        <h5 class="d-inline fw-bold">Sub-Agent Profile</h5>
                        <a href="{{ route('admin.subagent.index') }}" class="btn btn-primary float-right">
                            <i class="fas fa-arrow-left" style="font-size: 20px;"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Sub-Agent ID</label>
                                <input type="text" class="form-control" value="{{ $subAgent->user_name }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" class="form-control" value="{{ $subAgent->name }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" class="form-control" value="{{ $subAgent->phone }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="text" class="form-control" value="{{ $subAgent->email ?? 'N/A' }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Referral Code</label>
                                <input type="text" class="form-control" value="{{ $subAgent->referral_code }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Balance</label>
                                <input type="text" class="form-control" value="{{ number_format($subAgent->balanceFloat, 2) }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <input type="text" class="form-control" 
                                    value="{{ $subAgent->status == 1 ? 'Active' : 'Inactive' }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Created At</label>
                                <input type="text" class="form-control" 
                                    value="{{ $subAgent->created_at->format('Y-m-d H:i:s') }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

