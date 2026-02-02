@extends('layouts.master')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Game History</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.wager-list.index') }}">Wager List</a></li>
                        <li class="breadcrumb-item active">Game History</li>
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
                            <h3 class="card-title">Game History for Wager: <code>{{ $wagerCode }}</code></h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.wager-list.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Note:</strong> PG Soft products return content in HTML format. The content is displayed below.
                            </div>

                            @if(filter_var($content, FILTER_VALIDATE_URL))
                                <!-- If content is a URL, show it in an iframe -->
                                <div class="embed-responsive embed-responsive-16by9" style="min-height: 600px;">
                                    <iframe class="embed-responsive-item" src="{{ $content }}" allowfullscreen></iframe>
                                </div>
                                <div class="mt-3">
                                    <p><strong>Content URL:</strong> <a href="{{ $content }}" target="_blank">{{ $content }}</a></p>
                                </div>
                            @else
                                <!-- If content is HTML, display it directly -->
                                <div class="game-history-content">
                                    {!! $content !!}
                                </div>
                            @endif

                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card card-secondary">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-code mr-2"></i>Raw Content
                                            </h3>
                                        </div>
                                        <div class="card-body">
                                            <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">{{ htmlspecialchars($content) }}</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card card-info">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-info-circle mr-2"></i>API Information
                                            </h3>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Wager Code:</strong> <code>{{ $wagerCode }}</code></p>
                                            <p><strong>API Endpoint:</strong> <code>/api/operators/{{ $wagerCode }}/game-history</code></p>
                                            <p><strong>Content Type:</strong> 
                                                @if(filter_var($content, FILTER_VALIDATE_URL))
                                                    <span class="badge badge-success">URL</span>
                                                @else
                                                    <span class="badge badge-info">HTML</span>
                                                @endif
                                            </p>
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
    .game-history-content {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 20px;
        background: #fff;
        min-height: 400px;
    }
    .game-history-content iframe {
        width: 100%;
        min-height: 600px;
        border: none;
    }
</style>
@endsection

