@extends('layouts.master')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Sub-Agent List</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Sub-Agent List</li>
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
                     @can('player_create')
                    <div class="d-flex justify-content-end mb-3">
                        <a href="{{ route('admin.subagent.create') }}" class="btn btn-success " style="width: 100px;"><i
                                class="fas fa-plus text-white  mr-2"></i>Create</a>
                    </div>
                    @endcan
                    <div class="card">
                        <div class="card-body">
                            <table id="mytable" class="table table-bordered table-hover" data-disable-datatable="true">
                                <thead class="text-center">
                                    <th>#</th>
                                    <th>Sub-Agent Name</th>
                                    <th>Sub-Agent ID</th>
                                    <th>Referral Code</th>
                                    <th>Phone</th>
                                    <th>Commission</th>
                                    <th>Status</th>
                                    <th>Balance</th>
                                   @canany(['player_update','player_delete'])
                                    <th>Action</th>
                                   @endcanany
                                   @canany(['player_wallet_deposit','player_wallet_withdraw'])
                                    <th>Transfer</th>
                                   @endcanany
                                </thead>
                                <tbody >

                                    @if (isset($subAgents))
                                        @if (count($subAgents) > 0)
                                            @foreach ($subAgents as $subAgent)
                                                <tr class="text-center">
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        <span class="d-block">{{ $subAgent->name }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="d-block">{{ $subAgent->user_name }}</span>
                                                    </td>
                                                    <td>{{ $subAgent->referral_code }}</td>
                                                    <td>{{ $subAgent->phone }}</td>
                                                    <td>{{ $subAgent->commission }}%</td>
                                                    <td>
                                                        <small
                                                            class="badge bg-gradient-{{ $subAgent->status == 1 ? 'success' : 'danger' }}">{{ $subAgent->status == 1 ? 'active' : 'inactive' }}</small>

                                                    </td>
                                                    <td>{{ number_format($subAgent->balanceFloat) }}</td>

                                                    
                                            @canany(['player_update','player_delete'])
                                                    <td>
                                                        @if ($subAgent->status == 1)
                                                            <a onclick="event.preventDefault(); document.getElementById('banUser-{{ $subAgent->id }}').submit();"
                                                                class="me-2" href="#" data-bs-toggle="tooltip"
                                                                data-bs-original-title="Active Sub-Agent">
                                                                <i class="fas fa-user-check text-success"
                                                                    style="font-size: 20px;"></i>
                                                            </a>
                                                        @else
                                                            <a onclick="event.preventDefault(); document.getElementById('banUser-{{ $subAgent->id }}').submit();"
                                                                class="me-2" href="#" data-bs-toggle="tooltip"
                                                                data-bs-original-title="InActive Sub-Agent">
                                                                <i class="fas fa-user-slash text-danger"
                                                                    style="font-size: 20px;"></i>
                                                            </a>
                                                        @endif
                                                        <form class="d-none" id="banUser-{{ $subAgent->id }}"
                                                            action="{{ route('admin.subagent.ban', $subAgent->id) }}"
                                                            method="post">
                                                            @csrf
                                                            @method('PUT')
                                                        </form>

                                                        <a class="me-1"
                                                            href="{{ route('admin.subagent.getChangePassword', $subAgent->id) }}"
                                                            data-bs-toggle="tooltip"
                                                            data-bs-original-title="Change Password">
                                                            <i class="fas fa-lock text-info" style="font-size: 20px;"></i>
                                                        </a>
                                                        <a class="me-1" href="{{ route('admin.subagent.edit', $subAgent->id) }}"
                                                            data-bs-toggle="tooltip" data-bs-original-title="Edit Sub-Agent">
                                                            <i class="fas fa-edit text-info" style="font-size: 20px;"></i>
                                                        </a>
                                                    </td>
                                                @endcanany
                                                @canany(['player_wallet_deposit','player_wallet_withdraw'])
                                                    <td>
                                                        @can('player_wallet_deposit')
                                                        <a href="{{ route('admin.subagent.getCashIn', $subAgent->id) }}"
                                                            data-bs-toggle="tooltip"
                                                            data-bs-original-title="Deposit To Sub-Agent"
                                                            class="btn btn-info btn-sm">
                                                            <i class="fas fa-plus text-white mr-1"></i>Deposit
                                                        </a>
                                                        @endcan
                                                        @can('player_wallet_withdraw')
                                                        <a href="{{ route('admin.subagent.getCashOut', $subAgent->id) }}"
                                                            data-bs-toggle="tooltip"
                                                            data-bs-original-title="WithDraw From Sub-Agent"
                                                            class="btn btn-info btn-sm">
                                                            <i class="fas fa-minus text-white mr-1"></i>
                                                            Withdrawl
                                                        </a>
                                                        @endcan
                                                    </td>
                                                @endcanany
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="8">
                                                    There are no Sub-Agents.
                                                </td>
                                            </tr>
                                        @endif
                                    @endif
                                </tbody>

                            </table>
                        <div class="d-flex justify-content-center">
                            {{$subAgents->links()}}
                        </div>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        var successMessage = @json(session('successMessage'));
        var username = @json(session('username'));
        var password = @json(session('password'));
        var amount = @json(session('amount'));
        var link = @json(session('link'));

        @if (session()->has('successMessage'))
            toastr.success(successMessage +
                `
    <div>
        <button class="btn btn-primary btn-sm" data-toggle="modal"
            data-username="${username}"
            data-password="${password}"
            data-amount="${amount}"
            data-link="${link}"
            onclick="copyToClipboard(this)">Copy</button>
    </div>`, {
                    allowHtml: true
                });
        @endif

        function copyToClipboard(button) {
            var username = $(button).data('username');
            var password = $(button).data('password');
            var amount = $(button).data('amount');
            var link   = $(button).data('link');

            var textToCopy = "Username: " + username + "\nPassword: " + password + "\nAmount: " + amount + "\nLink: " + link;

            navigator.clipboard.writeText(textToCopy).then(function() {
                toastr.success("Credentials copied to clipboard!");
            }).catch(function(err) {
                toastr.error("Failed to copy text: " + err);
            });
        }
    </script>
@endsection

