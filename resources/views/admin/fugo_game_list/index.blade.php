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
                        <li class="breadcrumb-item active">Buffalo Game List</li>
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
                            <h5 class="mb-0">Buffalo Game List Dashboards
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
                                            <th class="bg-danger text-white">Game Name</th>
                                            <th class="bg-success text-white">Game Type</th>
                                            <th class="bg-danger text-white">Provider</th>
                                            <th class="bg-warning text-white">Image</th>
                                            <th class="bg-info text-white">Game ID</th>
                                            <th class="bg-info text-white">Room ID</th>
                                            <th class="bg-success text-white">Status</th>
                                            <th class="bg-info text-white">Hot Status</th>
                                            <th class="bg-warning text-white">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse ($fugo_game_lists as $fugo_game_list)
                                        <tr>
                                            <td>{{ $loop->iteration + $fugo_game_lists->firstItem() - 1 }}</td>
                                            <td>{{ $fugo_game_list->name }}</td>
                                            <td>{{ $fugo_game_list->type }}</td>
                                            <td>{{ $fugo_game_list->provider }}</td>
                                            <td>
                                                @if($fugo_game_list->image)
                                                    <img src="{{ $fugo_game_list->image }}" alt="{{ $fugo_game_list->name }}" width="100px">
                                                @else
                                                    <span class="text-muted">No Image</span>
                                                @endif
                                            </td>
                                            <td>{{ $fugo_game_list->gameId }}</td>
                                            <td>{{ $fugo_game_list->roomId }}</td>
                                            <td>
                                                <span class="badge {{ $fugo_game_list->status == 1 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $fugo_game_list->status == 1 ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $fugo_game_list->hot_status == 1 ? 'bg-success' : 'bg-info' }}">
                                                    {{ $fugo_game_list->hot_status == 1 ? 'HotGame' : 'NormalGame' }}
                                                </span>
                                            </td>
                                            <td>
                                                <form action="{{ route('admin.fugoGameLists.toggleHotStatus', $fugo_game_list->id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-{{ $fugo_game_list->hot_status == 1 ? 'danger' : 'success' }} btn-sm">
                                                        {{ $fugo_game_list->hot_status == 1 ? 'Set Normal' : 'Set Hot' }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center">No Buffalo games found.</td>
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
                                        Showing {{ $fugo_game_lists->firstItem() ?? 0 }} to {{ $fugo_game_lists->lastItem() ?? 0 }} of {{ $fugo_game_lists->total() }} results
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-7">
                                    <div class="dataTables_paginate paging_simple_numbers">
                                        {{ $fugo_game_lists->links('pagination::bootstrap-4') }}
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

