<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AZM999 | Dashboard</title>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/jqvmap/jqvmap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/summernote/summernote-bs4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css"
        href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">

    {{-- @vite(['resources/js/app.js']) --}}


    <style>
        .dropdown-menu {
            z-index: 1050 !important;
        }
    </style>

    @yield('style')


</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">



        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light sticky-top">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                            class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ route('home') }}" class="nav-link">Home</a>
                </li>
            </ul>



            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                @php
                    $authUser = auth()->user();
                    $isOwnerUser = $authUser && ((int) $authUser->type === \App\Enums\UserType::Owner->value || $authUser->roles->pluck('title')->contains('Owner'));
                    $isAgentUser = $authUser && ((int) $authUser->type === \App\Enums\UserType::Agent->value || $authUser->roles->pluck('title')->contains('Agent'));
                    $isSubAgentUser = $authUser && ((int) $authUser->type === \App\Enums\UserType::SubAgent->value || $authUser->roles->pluck('title')->contains('SubAgent'));
                @endphp
                @can('player_wallet_deposit')
                    @php
                        $unreadNotifications = auth()->user()->unreadNotifications;
                        $depositNotifications = $unreadNotifications->filter(function ($notification) {
                            return ($notification->data['type'] ?? 'deposit') === 'deposit';
                        });
                        $withdrawNotifications = $unreadNotifications->filter(function ($notification) {
                            return ($notification->data['type'] ?? '') === 'withdraw';
                        });
                    @endphp
                    <li class="nav-item dropdown mx-1">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="depositNotificationDropdown"
                            role="button" data-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-wallet2"></i>
                            <small class="text-muted ml-1">Deposit</small>
                            <span class="navbar-badge badge bg-danger text-white rounded-pill"
                                id="depositNotificationCount">{{ $depositNotifications->count() }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right shadow-lg p-0"
                            aria-labelledby="depositNotificationDropdown">
                            <li class="dropdown-header text-uppercase small text-secondary px-3 py-2">
                                Latest Deposit Requests
                            </li>
                            <li>
                                <hr class="dropdown-divider my-0">
                            </li>
                            <li class="px-0">
                                <div class="notification-list" id="depositNotificationList">
                                    @forelse ($depositNotifications as $notification)
                                        <a href="{{ route('admin.agent.deposit') }}"
                                            class="dropdown-item py-2 border-bottom d-block text-reset"
                                            data-notification-item="true"
                                            data-notification-type="deposit"
                                            data-notification-id="{{ $notification->id }}"
                                            data-notification-link="single">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-dark">{{ $notification->data['player_name'] }}</strong>
                                                <span class="badge bg-light text-dark">
                                                    {{ number_format($notification->data['amount'] ?? 0) }} Ks
                        </span>
                                            </div>
                                            <small class="text-secondary d-block">
                                                {{ $notification->data['message'] }}
                                            </small>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                {{ $notification->created_at->diffForHumans() }}
                                            </small>
                                        </a>
                                    @empty
                                        <p class="dropdown-item text-center text-muted mb-0" data-empty-state="deposit">
                                            No deposit notifications
                                        </p>
                                    @endforelse
                                </div>
                            </li>
                            <li>
                                <hr class="dropdown-divider my-0">
                        </li>
                        <li>
                                <button type="button" class="dropdown-item text-center text-secondary fw-semibold"
                                    data-notification-link="deposit-clear">
                                    Mark deposit notifications as read
                                </button>
                        </li>
                            <li>
                                <a href="{{ route('admin.agent.deposit') }}"
                                    class="dropdown-item text-center text-primary fw-bold"
                                    data-notification-link="deposit-all">
                                    Go to deposit requests
                                </a>
                        </li>
                    </ul>
                </li>

                    <li class="nav-item dropdown mx-1">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="withdrawNotificationDropdown"
                            role="button" data-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-cash-coin"></i>
                            <small class="text-muted ml-1">Withdraw</small>
                            <span class="navbar-badge badge bg-danger text-white rounded-pill"
                                id="withdrawNotificationCount">{{ $withdrawNotifications->count() }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right shadow-lg p-0"
                            aria-labelledby="withdrawNotificationDropdown">
                            <li class="dropdown-header text-uppercase small text-secondary px-3 py-2">
                                Latest Withdraw Requests
                    </li>
                    <li>
                                <hr class="dropdown-divider my-0">
                    </li>
                            <li class="px-0">
                                <div class="notification-list" id="withdrawNotificationList">
                                    @forelse ($withdrawNotifications as $notification)
                                        <a href="{{ route('admin.agent.withdraw') }}"
                                            class="dropdown-item py-2 border-bottom d-block text-reset"
                                            data-notification-item="true"
                                            data-notification-type="withdraw"
                                            data-notification-id="{{ $notification->id }}"
                                            data-notification-link="single">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-dark">{{ $notification->data['player_name'] }}</strong>
                                                <span class="badge bg-light text-dark">
                                                    {{ number_format($notification->data['amount'] ?? 0) }} Ks
                                                </span>
                                            </div>
                                            <small class="text-secondary d-block">
                                                {{ $notification->data['message'] }}
                                            </small>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                {{ $notification->created_at->diffForHumans() }}
                                            </small>
                                        </a>
                                    @empty
                                        <p class="dropdown-item text-center text-muted mb-0" data-empty-state="withdraw">
                                            No withdraw notifications
                                        </p>
                                    @endforelse
                                </div>
                            </li>
                            <li>
                                <hr class="dropdown-divider my-0">
                            </li>
                            <li>
                                <button type="button" class="dropdown-item text-center text-secondary fw-semibold"
                                    data-notification-link="withdraw-clear">
                                    Mark withdraw notifications as read
                                </button>
                            </li>
                            <li>
                                <a href="{{ route('admin.agent.withdraw') }}"
                                    class="dropdown-item text-center text-primary fw-bold"
                                    data-notification-link="withdraw-all">
                                    Go to withdraw requests
                        </a>
                    </li>
                        </ul>
                    </li>
                @endcan

                @if ($isAgentUser)
                    @php
                        $chatUnreadMessages = \App\Models\ChatMessage::query()
                            ->where('receiver_id', $authUser->id)
                            ->whereNull('read_at')
                            ->with(['sender:id,user_name,name'])
                            ->latest('id')
                            ->take(10)
                            ->get();
                        $chatUnreadCount = $chatUnreadMessages->count();
                    @endphp
                    <li class="nav-item dropdown mx-1">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="chatNotificationDropdown"
                            role="button" data-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-chat-dots"></i>
                            <small class="text-muted ml-1">Chat</small>
                            <span class="navbar-badge badge bg-danger text-white rounded-pill"
                                id="chatNotificationCount">{{ $chatUnreadCount }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right shadow-lg p-0"
                            aria-labelledby="chatNotificationDropdown">
                            <li class="dropdown-header text-uppercase small text-secondary px-3 py-2">
                                Latest player messages
                            </li>
                            <li>
                                <hr class="dropdown-divider my-0">
                            </li>
                            <li class="px-0">
                                <div class="notification-list" id="chatNotificationList">
                                    @forelse ($chatUnreadMessages as $chatMessage)
                                        <a href="{{ route('admin.chat.index') }}"
                                            class="dropdown-item py-2 border-bottom d-block text-reset">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-dark">
                                                    {{ $chatMessage->sender->user_name ?? $chatMessage->sender->name ?? 'Player' }}
                                                </strong>
                                                <small class="text-muted">
                                                    {{ $chatMessage->created_at?->diffForHumans() }}
                                                </small>
                                            </div>
                                            <small class="text-secondary d-block">
                                                {{ \Illuminate\Support\Str::limit($chatMessage->message, 80) }}
                                            </small>
                                        </a>
                                    @empty
                                        <p class="dropdown-item text-center text-muted mb-0">
                                            No unread chat messages
                                        </p>
                                    @endforelse
                                </div>
                            </li>
                            <li>
                                <hr class="dropdown-divider my-0">
                            </li>
                            <li>
                                <a href="{{ route('admin.chat.index') }}"
                                    class="dropdown-item text-center text-primary fw-bold">
                                    Go to chat center
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                <audio id="notificationSound" src="{{ asset('sounds/noti.wav') }}" preload="auto" class="d-none"></audio>
                <audio id="chatNotificationSound" src="{{ asset('sounds/livechat.mp3') }}" preload="auto" class="d-none"></audio>

                <li class="nav-item dropdown">
                    <a class="nav-link"
                        href="{{ route('admin.changePassword', \Illuminate\Support\Facades\Auth::id()) }}">
                        {{ auth()->user()->name }}
                        @if (auth()->user()->referral_code)
                            | {{ auth()->user()->referral_code }}
                        @endif
                    </a>
                </li>

                 <li class="nav-item">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        | Balance: {{ number_format(auth()->user()->balance, 2) }}
                    </a>
                </li> 

                <li class="nav-item dropdown">
                    <a class="nav-link" href="#"
                        onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>

                </li>

            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
             <a href="{{ route('home') }}" class="brand-link">
            <img src="{{ asset('assets/img/logo/logo.png') }}" alt="AdminLTE Logo"
                class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">SUPERHIT</span>
            </a>



            <!-- Sidebar -->
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <li class="nav-item menu-open">
                            <a href="{{ route('home') }}"
                                class="nav-link {{ Route::currentRouteName() == 'home' ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>
                                    Dashboard
                                </p>
                            </a>
                        </li>

                        @can('agent_view')
                            <li class="nav-item">
                                <a href="{{ route('admin.agent.index') }}"
                                    class="nav-link {{ Route::currentRouteName() == 'admin.agent.index' ? 'active' : '' }}">
                                    <i class="fas fa-users"></i>
                                    <p>
                                        Agent List
                                    </p>
                                </a>
                            </li>
                        @endcan

                        @can('player_view')
                            <li class="nav-item">
                                <a href="{{ route('admin.player.index') }}"
                                    class="nav-link {{ Route::currentRouteName() == 'admin.player.index' ? 'active' : '' }}">
                                    <i class="far fa-user"></i>
                                    <p>
                                        Player List
                                    </p>
                                </a>
                            </li>
                        @endcan

                        @if($isAgentUser && auth()->user()->can('player_view'))
                            <li class="nav-item">
                                <a href="{{ route('admin.subagent.index') }}"
                                    class="nav-link {{ in_array(Route::currentRouteName(), ['admin.subagent.index', 'admin.subagent.create', 'admin.subagent.edit', 'admin.subagent.show', 'admin.subagent.getCashIn', 'admin.subagent.getCashOut', 'admin.subagent.getChangePassword']) ? 'active' : '' }}">
                                    <i class="fas fa-user-friends"></i>
                                    <p>
                                        Sub-Agent List
                                    </p>
                                </a>
                            </li>
                        @endif

                        @can('bank_view')
                            <li class="nav-item">
                                <a href="{{ route('admin.bank.index') }}"
                                    class="nav-link {{ Route::currentRouteName() == 'admin.bank.index' ? 'active' : '' }}">
                                    <i class="fas fa-university"></i>
                                    <p>
                                        Bank
                                    </p>
                                </a>
                            </li>
                            <!-- contact  -->


                             <li class="nav-item">
                                <a href="{{ route('admin.contact.index') }}"
                                    class="nav-link {{ Route::currentRouteName() == 'admin.contact.index' ? 'active' : '' }}">
                                    <i class="fas fa-phone"></i>
                                    <p>
                                        Contact
                                    </p>
                                </a>
                            </li>
                        @endcan

                        @php
                            $user = $authUser;
                            $isAgent = $isAgentUser;
                            $agentChatRoutes = ['admin.chat.index', 'admin.chat.messages', 'admin.chat.messages.store'];
                        @endphp
                        
                        @if($isAgent)
                        <li class="nav-item">
                                <a href="{{ route('admin.transfer-logs.index') }}"
                                    class="nav-link {{ Route::currentRouteName() == 'admin.transfer-logs.index' ? 'active' : '' }}">
                                    <i class="fas fa-exchange-alt"></i>
                                    <p>
                                        Transaction Log
                                    </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.agent.withdraw') }}"
                                    class="nav-link {{ Route::currentRouteName() == 'admin.agent.withdraw' ? 'active' : '' }}">
                                    <i class="fas fa-comment-dollar"></i>
                                    <p>
                                        Withdraw Request
                                    </p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.agent.deposit') }}"
                                    class="nav-link {{ Route::currentRouteName() == 'admin.agent.deposit' ? 'active' : '' }}">
                                    <i class="fab fa-dochub"></i>
                                    <p>
                                        Deposit Request
                                    </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.chat.index') }}"
                                    class="nav-link {{ in_array(Route::currentRouteName(), $agentChatRoutes, true) ? 'active' : '' }}">
                                    <i class="fas fa-comments"></i>
                                    <p>
                                        Player Chat
                                    </p>
                                </a>
                            </li>
                        @endif

                        @can('agent_wallet_deposit')
                            <li class="nav-item">
                                <a href="{{ route('admin.transfer-logs.index') }}"
                                    class="nav-link {{ Route::currentRouteName() == 'admin.transfer-logs.index' ? 'active' : '' }}">
                                    <i class="fas fa-exchange-alt"></i>
                                    <p>
                                        Transaction Log
                                    </p>
                                </a>
                            </li>
                        @endcan

                        @canany(['report_accept','player_view'])
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-file-invoice"></i>
                                    <p>
                                        Reports
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @if($isSubAgentUser)
                                        <li class="nav-item">
                                            <a href="{{ route('admin.report.subagent-commission') }}"
                                                class="nav-link {{ Route::currentRouteName() == 'admin.report.subagent-commission' ? 'active' : '' }}">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Sub-Agent Commission Report</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('admin.reports.buffalo_subagent_commission') }}"
                                                class="nav-link {{ Route::currentRouteName() == 'admin.reports.buffalo_subagent_commission' ? 'active' : '' }}">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Buffalo Sub-Agent Commission</p>
                                            </a>
                                        </li>
                                    @else
                                        <li class="nav-item">
                                            <a href="{{ route('admin.buffalo-report.index') }}"
                                                class="nav-link {{ in_array(Route::currentRouteName(), ['admin.buffalo-report.index', 'admin.buffalo-report.show']) ? 'active' : '' }}">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Buffalo Game Report</p>
                                            </a>
                                        </li>
                                        @if($isOwnerUser || $isAgentUser)
                                            @can('report_accept')
                                            <li class="nav-item">
                                                <a href="{{ route('admin.report.index') }}"
                                                    class="nav-link {{ Route::current()->getName() == 'admin.report.index' ? 'active' : '' }}">
                                                    <i class="far fa-circle nav-icon"></i>
                                                    <p>GSC Win/Lose Report</p>
                                                </a>
                                            </li>
                                            @endcan
                                            @can('report_accept')
                                            <li class="nav-item">
                                                <a href="{{ route('admin.reports.daily_win_loss') }}"
                                                    class="nav-link {{ Route::currentRouteName() == 'admin.reports.daily_win_loss' ? 'active' : '' }}">
                                                    <i class="nav-icon fas a-chart-line"></i>
                                                    <p>GSC Daily Win/Loss</p>
                                                </a>
                                            </li>
                                            @endcan
                                        @endif
                                        <li class="nav-item">
                                            <a href="{{ route('admin.report.subagent-commission') }}"
                                                class="nav-link {{ Route::currentRouteName() == 'admin.report.subagent-commission' ? 'active' : '' }}">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Sub-Agent Commission Report</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('admin.reports.buffalo_subagent_commission') }}"
                                                class="nav-link {{ Route::currentRouteName() == 'admin.reports.buffalo_subagent_commission' ? 'active' : '' }}">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Buffalo Sub-Agent Commission</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endcanany

                        @canany(['banner_view','banner_text_view','promotion_view'])
                            <li class="nav-item {{ in_array(Route::currentRouteName(), ['admin.video-upload.index', 'admin.text.index', 'admin.banners.index', 'admin.adsbanners.index', 'admin.promotions.index']) ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-tools"></i>
                                    <p>
                                        General Settings
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('banner_view')
                                        <li class="nav-item">
                                            <a href="{{ route('admin.video-upload.index') }}"
                                                class="nav-link {{ Route::currentRouteName() == 'admin.video-upload.index' ? 'active' : '' }}">
                                                <i class="fas fa-video nav-icon"></i>
                                                <p>AdsVideo</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('admin.banners.index') }}"
                                                class="nav-link {{ Route::currentRouteName() == 'admin.banners.index' ? 'active' : '' }}">
                                                <i class="fas fa-image nav-icon"></i>
                                                <p>Banner</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('admin.adsbanners.index') }}"
                                                class="nav-link {{ Route::currentRouteName() == 'admin.adsbanners.index' ? 'active' : '' }}">
                                                <i class="fas fa-ad nav-icon"></i>
                                                <p>Banner Ads</p>
                                            </a>
                                        </li>
                                    @endcan

                                    @can('banner_text_view')
                                        <li class="nav-item">
                                            <a href="{{ route('admin.text.index') }}"
                                                class="nav-link {{ Route::currentRouteName() == 'admin.text.index' ? 'active' : '' }}">
                                                <i class="fas fa-font nav-icon"></i>
                                                <p>BannerText</p>
                                            </a>
                                        </li>
                                    @endcan

                                    @can('promotion_view')
                                        <li class="nav-item">
                                            <a href="{{ route('admin.promotions.index') }}"
                                                class="nav-link {{ Route::currentRouteName() == 'admin.promotions.index' ? 'active' : '' }}">
                                                <i class="fas fa-bullhorn nav-icon"></i>
                                                <p>Promotions</p>
                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                            </li>

                            @can('agent_view')
                           <li
                                class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-tools"></i>
                                    <p>
                                        GSCPLUS Settings
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.gameLists.index') }}"
                                            class="nav-link {{ Route::current()->getName() == 'admin.gameLists.index' ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>GSCPLUS GameList</p>
                                        </a>
                                    </li>
                                    @if($isOwnerUser)
                                        <li class="nav-item">
                                            <a href="{{ route('admin.fugoGameLists.index') }}"
                                                class="nav-link {{ Route::current()->getName() == 'admin.fugoGameLists.index' ? 'active' : '' }}">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Buffalo Game List</p>
                                            </a>
                                        </li>
                                    @endif
                                    <li class="nav-item">
                                        <a href="{{ route('admin.gametypes.index') }}"
                                            class="nav-link {{ Route::current()->getName() == 'admin.gametypes.index' ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>GSCPLUS Provider</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.wager-list.index') }}"
                                            class="nav-link {{ in_array(Route::current()->getName(), ['admin.wager-list.index', 'admin.wager-list.fetch']) ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Wager List</p>
                                        </a>
                                    </li>

                                     <!-- <li class="nav-item">
                                        <a href=""
                                            class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>GSC GameType</p>
                                        </a>
                                    </li>  -->
                                </ul>
                            </li>
                        @endcan
                        @endcanany
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <div class="content-wrapper">

            @yield('content')
        </div>
        <footer class="main-footer">
            <strong>Copyright &copy; 2026 <a href="">AZM999</a>.</strong>
            All rights reserved.
            <div class="float-right d-none d-sm-inline-block">
                <b>Version</b> 2.0.0
            </div>
        </footer>

        <aside class="control-sidebar control-sidebar-dark">
        </aside>
    </div>

    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        // $.widget.bridge('uibutton', $.ui.button)

        // Setup CSRF token for all AJAX requests
        // data table updated
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    <script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('plugins/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('plugins/summernote/summernote-bs4.min.js') }}"></script>
    <script src="{{ asset('plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
    <script src="{{ asset('js/adminlte.js') }}"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>

    <!-- DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

    @yield('script')
    <script>
        var errorMessage = @json(session('error'));
        var successMessage = @json(session('success'));

        @if (session()->has('success'))
            toastr.success(successMessage)
        @elseif (session()->has('error'))
            toastr.error(errorMessage)
        @endif
    </script>
    <script>
        $(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });
            $('#ponewineTable').DataTable();
            $('#slotTable').DataTable();

            const $myTable = $('#mytable');
            const datatableDisabled = $myTable.data('disable-datatable');

            if ($myTable.length && !datatableDisabled) {
                $myTable.DataTable({
                    "responsive": true,
                    "lengthChange": false,
                    "autoWidth": false,
                    "order": true,
                    "pageLength": 10,
                }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
            }
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl)
            })
        });
    </script>

    @php
        $notificationServer = rtrim((string) config('notification.server_url'), '/');
        $markReadRoute = route('notifications.mark-read');
        $unreadRoute = route('notifications.unread');
    @endphp

    @if ($notificationServer)
        <script src="{{ $notificationServer }}/socket.io/socket.io.js"></script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notificationServer = @json($notificationServer);
            const authUserId = @json(auth()->id());
            const audioEl = document.getElementById('notificationSound');
            const chatAudioEl = document.getElementById('chatNotificationSound');
            const depositListEl = document.getElementById('depositNotificationList');
            const withdrawListEl = document.getElementById('withdrawNotificationList');
            const depositCountEl = document.getElementById('depositNotificationCount');
            const withdrawCountEl = document.getElementById('withdrawNotificationCount');
            const chatCountEl = document.getElementById('chatNotificationCount');
            const chatListEl = document.getElementById('chatNotificationList');
            const chatCenterUrl = @json(route('admin.chat.index'));
            const CHAT_MAX_CHAT_ITEMS = 10;
            let audioUnlocked = false;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const markReadUrl = @json($markReadRoute);
            const unreadUrl = @json($unreadRoute);
            const depositPageUrl = @json(route('admin.agent.deposit'));
            const withdrawPageUrl = @json(route('admin.agent.withdraw'));

            const currentNotifications = {
                deposit: [],
                withdraw: [],
            };

            const listMap = {
                deposit: {
                    list: depositListEl,
                    count: depositCountEl,
                    emptyText: 'No deposit notifications',
                    defaultRoute: depositPageUrl,
                },
                withdraw: {
                    list: withdrawListEl,
                    count: withdrawCountEl,
                    emptyText: 'No withdraw notifications',
                    defaultRoute: withdrawPageUrl,
                },
            };

            const playSound = (audioElement) => {
                if (!audioElement) {
                    return;
                }

                audioElement.currentTime = 0;
                const playPromise = audioElement.play();

                if (playPromise?.catch) {
                    playPromise.catch(() => {
                        /* ignored - autoplay restrictions */
                    });
                }
            };

            const playNotificationSound = () => playSound(audioEl);
            const playChatSound = () => playSound(chatAudioEl);

            const attemptUnlockAudio = () => {
                if (audioUnlocked) {
                    return;
                }

                const unlock = (element) => {
                    if (!element) {
                        return Promise.resolve();
                    }

                    element.muted = true;
                    return element.play()
                        .then(() => {
                            element.pause();
                            element.currentTime = 0;
                            element.muted = false;
                        })
                        .catch(() => {
                            element.muted = false;
                        });
                };

                Promise.all([unlock(audioEl), unlock(chatAudioEl)]).finally(() => {
                    audioUnlocked = true;
                });
            };

            document.addEventListener('click', attemptUnlockAudio, { once: true });
            document.addEventListener('touchstart', attemptUnlockAudio, { once: true });

            const setChatBadgeValue = (value) => {
                if (!chatCountEl) {
                    return;
                }

                const sanitized = Math.max(0, Number(value) || 0);
                chatCountEl.textContent = sanitized;
            };

            const incrementChatBadge = (delta = 1) => {
                if (!chatCountEl) {
                    return;
                }

                const current = Number(chatCountEl.textContent || 0);
                setChatBadgeValue(current + delta);
            };

            const decrementChatBadge = (delta = 1) => {
                if (!chatCountEl) {
                    return;
                }

                const current = Number(chatCountEl.textContent || 0);
                setChatBadgeValue(current - delta);
            };

            const appendChatNotification = (payload) => {
                if (!chatListEl) {
                    return;
                }

                const data = payload?.notification_data ?? {};
                const playerName = data.player_user_name ?? payload?.title ?? 'Player';
                const messageBody = payload?.body ?? data.message ?? 'New chat message';
                const createdAt = data.created_at ?? '';

                const emptyState = chatListEl.querySelector('[data-chat-empty-state]');
                if (emptyState) {
                    emptyState.remove();
                }

                const item = document.createElement('a');
                item.href = chatCenterUrl;
                item.className = 'dropdown-item py-2 border-bottom d-block text-reset';
                item.dataset.chatItem = 'true';
                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <strong class="text-dark">${playerName}</strong>
                        <small class="text-muted">${createdAt}</small>
                    </div>
                    <small class="text-secondary d-block">${messageBody}</small>
                `;

                chatListEl.prepend(item);

                const chatItems = chatListEl.querySelectorAll('[data-chat-item]');
                if (chatItems.length > CHAT_MAX_CHAT_ITEMS) {
                    Array.from(chatItems)
                        .slice(CHAT_MAX_CHAT_ITEMS)
                        .forEach((node) => node.remove());
                }
            };

            const updateBadge = (type, value) => {
                const target = listMap[type]?.count;
                if (!target) {
                    return;
                }

                target.textContent = Math.max(0, Number(value) || 0);
            };

            const handleSingleNotificationClick = (event, type, item) => {
                event.preventDefault();

                markNotificationsAsRead([item.id]).finally(() => {
                    window.location.href = item.route || listMap[type].defaultRoute;
                });
            };

            const renderNotifications = (type, items = []) => {
                const map = listMap[type];
                if (!map?.list) {
                    return;
                }

                currentNotifications[type] = items;
                map.list.innerHTML = '';

                if (!items.length) {
                    map.list.innerHTML =
                        `<p class="dropdown-item text-center text-muted mb-0" data-empty-state="${type}">${map.emptyText}</p>`;
                    updateBadge(type, 0);

                    return;
                }

                items.forEach((item) => {
                    const link = document.createElement('a');
                    link.href = item.route || map.defaultRoute;
                    link.className = 'dropdown-item py-2 border-bottom d-block text-reset';
                    link.dataset.notificationItem = 'true';
                    link.dataset.notificationId = item.id;
                    link.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-dark">${item.player_name ?? 'Player'}</strong>
                            <span class="badge bg-light text-dark">${Number(item.amount ?? 0).toLocaleString()} Ks</span>
                        </div>
                        <small class="d-block text-secondary">${item.message ?? ''}</small>
                        <small class="text-muted"><i class="bi bi-clock me-1"></i>${item.created_at_human ?? ''}</small>
                    `;
                    link.addEventListener('click', (event) => handleSingleNotificationClick(event, type, item));
                    map.list.appendChild(link);
                });

                updateBadge(type, items.length);
            };

            const getNotificationIds = (type) => currentNotifications[type]?.map((item) => item.id) ?? [];

            const markNotificationsAsRead = (ids = []) => {
                if (!ids.length || !markReadUrl) {
                    return Promise.resolve();
                }

                return fetch(markReadUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ ids }),
                    })
                    .catch(() => {})
                    .finally(() => {
                        fetchUnreadNotifications();
                    });
            };

            const fetchUnreadNotifications = () => {
                if (!unreadUrl) {
                    return;
                }

                fetch(unreadUrl, {
                        headers: {
                            'Accept': 'application/json',
                        },
                    })
                    .then((response) => response.json())
                    .then((data) => {
                        renderNotifications('deposit', data.notifications?.deposit ?? []);
                        renderNotifications('withdraw', data.notifications?.withdraw ?? []);
                    })
                    .catch(() => {});
            };

            const handleClear = (type) => {
                const ids = getNotificationIds(type);
                if (!ids.length) {
                    return;
                }

                markNotificationsAsRead(ids);
            };

            const handleNavigate = (event, type) => {
                const ids = getNotificationIds(type);
                if (!ids.length) {
                    return;
                }

                event.preventDefault();
                const targetHref = event.currentTarget?.href || listMap[type].defaultRoute;
                markNotificationsAsRead(ids).finally(() => {
                    window.location.href = targetHref;
                });
            };

            document.querySelector('[data-notification-link="deposit-clear"]')
                ?.addEventListener('click', (event) => {
                    event.preventDefault();
                    handleClear('deposit');
                });

            document.querySelector('[data-notification-link="deposit-all"]')
                ?.addEventListener('click', (event) => handleNavigate(event, 'deposit'));

            document.querySelector('[data-notification-link="withdraw-clear"]')
                ?.addEventListener('click', (event) => {
                    event.preventDefault();
                    handleClear('withdraw');
                });

            document.querySelector('[data-notification-link="withdraw-all"]')
                ?.addEventListener('click', (event) => handleNavigate(event, 'withdraw'));

            const showToast = (type, message) => {
                if (!window.toastr || !message) {
                    return;
                }

                const title = type === 'withdraw' ? 'Withdraw Request' : 'Deposit Request';
                window.toastr.info(message, title);
            };

            fetchUnreadNotifications();

            if (notificationServer && typeof io !== 'undefined') {
                const socket = io(notificationServer, {
                    transports: ['websocket', 'polling'],
                });

                socket.on('connect', function() {
                    if (authUserId) {
                        socket.emit('register', authUserId);
                    }
                });

                socket.on('receive_noti', function(payload) {
                    const type = (payload?.notification_data?.type || payload?.type || 'deposit').toLowerCase();
                    const message = payload?.body
                        || payload?.notification_data?.message
                        || payload?.notification_data?.body
                        || 'You have a new notification.';

                    if (type === 'chat') {
                        playChatSound();
                        incrementChatBadge(1);
                        appendChatNotification(payload);
                        window.dispatchEvent(new CustomEvent('chat-notification', { detail: payload }));
                        showToast('chat', message);
                        return;
                    }

                    const normalizedType = type === 'withdraw' ? 'withdraw' : 'deposit';
                    playNotificationSound();
                    showToast(normalizedType, message);
                    fetchUnreadNotifications();
                });

                socket.on('disconnect', function() {
                    console.warn('Disconnected from notification server');
                });
            }

            

            window.addEventListener('chat-sync-badge', (event) => {
                setChatBadgeValue(event.detail?.count ?? 0);
            });
            window.addEventListener('chat-read', () => {
                decrementChatBadge(1);
            });
        });
    </script>



</body>

</html>
