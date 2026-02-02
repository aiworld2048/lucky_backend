@extends('layouts.master')
@section('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
@endsection
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-12">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">GSCPLUS GameList</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-end mb-3">

                    </div>
                    <div class="card " style="border-radius: 20px;">
                        <div class="card-header">
                            <h5 class="mb-0">Game List Dashboards
                                <span>
                                    <p>
                                    </p>
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                                <div class="table-responsive">
                                <table id="mytable" class="table table-bordered table-hover" data-disable-datatable="true">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th class="bg-danger text-white">GameName</th>
                                            <th class="bg-success text-white">Game Type</th>
                                            <th class="bg-danger text-white">Provider</th>
                                            <th class="bg-warning text-white">Image</th>
                                            <th class="bg-success text-white">CloseStatus</th>
                                            <th class="bg-info text-white">Hot Status</th>
                                            <th class="bg-warning text-white">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse ($game_lists as $game_list)
                                        <tr>
                                            <td>{{ $loop->iteration + $game_lists->firstItem() - 1 }}</td>
                                            <td>{{ $game_list->game_name }}</td>
                                            <td>{{ $game_list->game_type }}</td>
                                            <td>{{ $game_list->provider }}</td>
                                            <td>
                                                <img src="{{ $game_list->image_url }}" alt="" width="100px">
                                            </td>
                                            <td>{{ $game_list->status }}</td>
                                            <td>
                                            <span class="badge {{ $game_list->hot_status == 1 ? 'bg-success' : 'bg-info' }}">
                                                {{ $game_list->hot_status == 1 ? 'HotGame' : 'NormalGame' }}
                                            </span>
                                        </td>
                                        <td>
                                            <form action="{{ route('admin.HotGame.toggleStatus', $game_list->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-{{ $game_list->hot_status == 1 ? 'danger' : 'success' }} btn-sm">
                                                    {{ $game_list->hot_status == 1 ? 'Set Normal' : 'Set Hot' }}
                                                </button>
                                            </form>
                                        </td>

                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No games found.</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                                </div>
                        </div>
                        <!-- /.card-body -->
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-sm-12 col-md-5">
                                    <div class="dataTables_info" role="status" aria-live="polite">
                                        Showing {{ $game_lists->firstItem() ?? 0 }} to {{ $game_lists->lastItem() ?? 0 }} of {{ $game_lists->total() }} results
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-7">
                                    <div class="dataTables_paginate paging_simple_numbers">
                                        {{ $game_lists->links('pagination::bootstrap-4') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card -->
                </div>
            </div>
        </div>
    </section>
@endsection

