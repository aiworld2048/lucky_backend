<?php

use App\Http\Controllers\Admin\AdsVedioController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\SubAgentController;
use App\Http\Controllers\Admin\BankController;
use App\Http\Controllers\Admin\BannerAdsController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BannerTextController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DepositRequestController;
use App\Http\Controllers\Admin\PaymentTypeController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\TransferLogController;
use App\Http\Controllers\Admin\WithDrawRequestController;
use App\Http\Controllers\Admin\BuffaloGame\BuffaloReportController;
use App\Http\Controllers\Admin\BuffaloGame\BuffaloSubAgentCommissionController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\PlayerReportController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\GameListController;
use App\Http\Controllers\Admin\FugoGameListController;
use App\Http\Controllers\Admin\WagerListController;


Route::group([
    'prefix' => 'admin',
    'as' => 'admin.',
    'middleware' => ['auth', 'checkBanned'],
], function () {

    Route::post('balance-up', [HomeController::class, 'balanceUp'])->name('balanceUp');

    Route::get('logs/{id}', [HomeController::class, 'logs'])->name('logs');

    // to do
    Route::get('/changePassword/{user}', [HomeController::class, 'changePassword'])->name('changePassword');
    Route::post('/updatePassword/{user}', [HomeController::class, 'updatePassword'])->name('updatePassword');

    Route::get('/changeplayersite/{user}', [HomeController::class, 'changePlayerSite'])->name('changeSiteName');

    Route::post('/updatePlayersite/{user}', [HomeController::class, 'updatePlayerSiteLink'])->name('updateSiteLink');

    Route::get('/player-list', [HomeController::class, 'playerList'])->name('playerList');


     // banner etc start

    Route::resource('video-upload', AdsVedioController::class)->middleware('permission:banner_view|banner_create|banner_update|banner_delete');

    Route::resource('banners', BannerController::class)->middleware('permission:banner_view|banner_create|banner_update|banner_delete');
    Route::resource('adsbanners', BannerAdsController::class)->middleware('permission:banner_view|banner_create|banner_update|banner_delete');
    Route::resource('text', BannerTextController::class)->middleware('permission:banner_text_view|banner_text_create|banner_text_update|banner_text_delete');
    Route::resource('/promotions', PromotionController::class)->middleware('permission:promotion_view|promotion_create|promotion_update|promotion_delete');
    Route::resource('contact', ContactController::class);
    Route::resource('paymentTypes', PaymentTypeController::class);
    Route::resource('bank', BankController::class)->middleware('permission:bank_view|bank_create|bank_update|bank_delete');
    // agent start
    Route::controller(AgentController::class)
        ->prefix('agent')
        ->name('agent.')
        ->group(function () {
            Route::get('/', 'index')->middleware('permission:agent_index')->name('index');
            Route::get('/create', 'create')->middleware('permission:agent_create')->name('create');
            Route::post('/', 'store')->middleware('permission:agent_create')->name('store');
            Route::get('/{agent}/edit', 'edit')->middleware('permission:agent_edit')->name('edit');
            Route::put('/{agent}', 'update')->middleware('permission:agent_edit')->name('update');

            Route::put('/{agent}/ban', 'banAgent')->middleware('permission:agent_edit')->name('ban');

            Route::get('/{agent}/change-password', 'getChangePassword')
                ->middleware('permission:agent_change_password_access')
                ->name('getChangePassword');
            Route::post('/{agent}/change-password', 'makeChangePassword')
                ->middleware('permission:agent_change_password_access')
                ->name('makeChangePassword');

            Route::get('/{agent}/cash-in', 'getCashIn')
                ->middleware('permission:make_transfer')
                ->name('getCashIn');
            Route::post('/{agent}/cash-in', 'makeCashIn')
                ->middleware('permission:make_transfer')
                ->name('makeCashIn');
            Route::get('/{agent}/cash-out', 'getCashOut')
                ->middleware('permission:make_transfer')
                ->name('getCashOut');
            Route::post('/{agent}/cash-out', 'makeCashOut')
                ->middleware('permission:make_transfer')
                ->name('makeCashOut');

            Route::get('/{agent}/report', 'agentReportIndex')
                ->middleware('permission:transfer_log')
                ->name('report');
            Route::get('/{agent}/player-report', 'getPlayerReports')
                ->middleware('permission:transfer_log')
                ->name('getPlayerReports');
            Route::get('/{agent}/profile', 'agentProfile')
                ->middleware('permission:agent_access')
                ->name('profile');
        });
    // agent end

    // Sub-Agent routes start (for Agents to manage sub-agents)
    Route::controller(SubAgentController::class)
        ->prefix('subagent')
        ->name('subagent.')
        ->group(function () {
            Route::get('/', 'index')->middleware('permission:player_view')->name('index');
            Route::get('/create', 'create')->middleware('permission:player_create')->name('create');
            Route::post('/', 'store')->middleware('permission:player_create')->name('store');
            Route::get('/{subagent}/edit', 'edit')->middleware('permission:player_update')->name('edit');
            Route::put('/{subagent}', 'update')->middleware('permission:player_update')->name('update');
            Route::get('/{subagent}', 'show')->middleware('permission:player_view')->name('show');

            Route::put('/{subagent}/ban', 'banSubAgent')->middleware('permission:player_update')->name('ban');

            Route::get('/{subagent}/change-password', 'getChangePassword')
                ->middleware('permission:player_password_change')
                ->name('getChangePassword');
            Route::post('/{subagent}/change-password', 'makeChangePassword')
                ->middleware('permission:player_password_change')
                ->name('makeChangePassword');

            Route::get('/{subagent}/cash-in', 'getCashIn')
                ->middleware('permission:player_wallet_deposit')
                ->name('getCashIn');
            Route::post('/{subagent}/cash-in', 'makeCashIn')
                ->middleware('permission:player_wallet_deposit')
                ->name('makeCashIn');
            Route::get('/{subagent}/cash-out', 'getCashOut')
                ->middleware('permission:player_wallet_withdraw')
                ->name('getCashOut');
            Route::post('/{subagent}/cash-out', 'makeCashOut')
                ->middleware('permission:player_wallet_withdraw')
                ->name('makeCashOut');
        });
    // Sub-Agent routes end

    
    
    // Player list start
    Route::middleware(['permission:player_view'])->group(function () {
        Route::get('players', [PlayerController::class, 'index'])->name('player.index');
        Route::get('players/{player}', [PlayerController::class, 'show'])->name('player.show');

        Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
        Route::get('chat/{player}/messages', [ChatController::class, 'messages'])->name('chat.messages');
        Route::post('chat/{player}/messages', [ChatController::class, 'store'])->name('chat.messages.store');
    });

    Route::middleware(['permission:player_update'])->group(function () {
        Route::get('players/{player}/edit', [PlayerController::class, 'edit'])->name('player.edit');
        Route::put('players/{player}', [PlayerController::class, 'update'])->name('player.update');
    });

    // Player creation routes
    Route::get('agent/players/create', [PlayerController::class, 'create'])
        ->middleware('permission:player_create')
        ->name('agent.player.create');
    Route::post('agent/players', [PlayerController::class, 'store'])
        ->middleware('permission:player_create')
        ->name('agent.player.store');
    

    // Withdraw routes (for process_withdraw permission)
    Route::middleware(['permission:agent_wallet_withdraw'])->group(function () {
        Route::get('finicialwithdraw', [WithDrawRequestController::class, 'index'])->name('agent.withdraw');
        Route::post('finicialwithdraw/{withdraw}', [WithDrawRequestController::class, 'statusChangeIndex'])->name('agent.withdrawStatusUpdate');
        Route::post('finicialwithdraw/reject/{withdraw}', [WithDrawRequestController::class, 'statusChangeReject'])->name('agent.withdrawStatusreject');
        Route::get('finicialwithdraw/{withdraw}', [WithDrawRequestController::class, 'WithdrawShowLog'])->name('agent.withdrawLog');
    });

    // Deposit routes (for both parent agents and sub-agents)
    Route::middleware(['permission:agent_wallet_deposit'])->group(function () {
        Route::get('finicialdeposit', [DepositRequestController::class, 'index'])->name('agent.deposit');
        Route::get('finicialdeposit/{deposit}', [DepositRequestController::class, 'view'])->name('agent.depositView');
        Route::post('finicialdeposit/{deposit}', [DepositRequestController::class, 'statusChangeIndex'])->name('agent.depositStatusUpdate');
        Route::post('finicialdeposit/reject/{deposit}', [DepositRequestController::class, 'statusChangeReject'])->name('agent.depositStatusreject');
        Route::get('finicialdeposit/{deposit}/log', [DepositRequestController::class, 'DepositShowLog'])->name('agent.depositLog');
    });

    // Cash-in/cash-out routes
    Route::middleware(['permission:player_update'])->group(function () {
        Route::get('player-cash-in/{player}', [PlayerController::class, 'getCashIn'])->name('player.getCashIn');
        Route::post('player-cash-in/{player}', [PlayerController::class, 'makeCashIn'])->name('player.makeCashIn');
        Route::get('player/cash-out/{player}', [PlayerController::class, 'getCashOut'])->name('player.getCashOut');
        Route::post('player-cash-out/update/{player}', [PlayerController::class, 'makeCashOut'])->name('player.makeCashOut');
    });

    // Player ban route
    Route::middleware(['permission:player_ban'])->group(function () {
        Route::put('player/{id}/ban', [PlayerController::class, 'banUser'])->name('player.ban');
    });

    // Player change password routes
    Route::middleware(['permission:player_password_change'])->group(function () {
        Route::get('player-changepassword/{id}', [PlayerController::class, 'getChangePassword'])->name('player.getChangePassword');
        Route::post('player-changepassword/{id}', [PlayerController::class, 'makeChangePassword'])->name('player.makeChangePassword');
    });

    Route::get('/players-list', [PlayerController::class, 'player_with_agent'])
        ->middleware('permission:player_view')
        ->name('playerListForAdmin');
    
    // Player report route
    Route::get('player/{player}/report', [PlayerController::class, 'playerReportIndex'])
        ->middleware('permission:player_view')
        ->name('player.report_detail');
    
    // agent create player end
    // report log

    //  agent end
    Route::get('/transfer-logs', [TransferLogController::class, 'index'])
        ->middleware('permission:agent_wallet_deposit|agent_wallet_withdraw')
        ->name('transfer-logs.index');

   
    Route::get('playertransferlog/{id}', [TransferLogController::class, 'PlayertransferLog'])
        ->middleware('permission:player_view')
        ->name('PlayertransferLogDetail');

    

    // Buffalo Game reports
    Route::group(['prefix' => 'buffalo-game'], function () {
        Route::get('/report', [BuffaloReportController::class, 'index'])->name('buffalo-report.index');
        Route::get('/report/{id}', [BuffaloReportController::class, 'show'])->name('buffalo-report.show');
    });


    // gsc report
    Route::get('report', [ReportController::class, 'index'])->name('report.index');
    Route::get('report/{member_account}', [ReportController::class, 'show'])->name('report.detail');
    Route::get('player-report', [PlayerReportController::class, 'summary'])->name('player_report.summary');
    Route::get('reports/daily-win-loss', [ReportController::class, 'dailyWinLossReport'])->name('reports.daily_win_loss');
    Route::get('reports/game-log-report', [ReportController::class, 'gameLogReport'])->name('reports.game_log_report');
    Route::get('reports/buffalo-subagent-commission', [BuffaloSubAgentCommissionController::class, 'index'])->name('reports.buffalo_subagent_commission');
    //Route::get('reports/combined-game-report', [\App\Http\Controllers\Admin\CombinedGameReportController::class, 'index'])->name('reports.combined_game_report');

    Route::get('reports/player-report/{playerId}',[ReportController::class,'getReport'])->name('report.player.index');
    Route::get('reports/subagent-commission', [ReportController::class, 'subAgentCommissionReport'])->name('report.subagent-commission');

    // game list and product type list

     // provider start
     Route::get('gametypes', [ProductController::class, 'index'])->name('gametypes.index');
     Route::post('/game-types/{productId}/toggle-status', [ProductController::class, 'toggleStatus'])->name('gametypes.toggle-status');
     Route::get('gametypes/{game_type_id}/product/{product_id}', [ProductController::class, 'edit'])->name('gametypes.edit');
     Route::post('gametypes/{game_type_id}/product/{product_id}', [ProductController::class, 'update'])->name('gametypes.update');
     Route::post('admin/gametypes/{gameTypeId}/{productId}/update', [ProductController::class, 'update'])
         ->name('gametypesproduct.update');
 
     // game list start
     Route::get('all-game-lists', [GameListController::class, 'GetGameList'])->name('gameLists.index');
     Route::get('all-game-lists/{id}', [GameListController::class, 'edit'])->name('gameLists.edit');
     Route::post('all-game-lists/{id}', [GameListController::class, 'update'])->name('gameLists.update');
 
     Route::patch('gameLists/{id}/toggleStatus', [GameListController::class, 'toggleStatus'])->name('gameLists.toggleStatus');
 
     Route::patch('hotgameLists/{id}/toggleStatus', [GameListController::class, 'HotGameStatus'])->name('HotGame.toggleStatus');
 
     // pp hot
 
     Route::patch('pphotgameLists/{id}/toggleStatus', [GameListController::class, 'PPHotGameStatus'])->name('PPHotGame.toggleStatus');
     Route::get('game-list/{gameList}/edit', [GameListController::class, 'edit'])->name('game_list.edit');
     Route::post('/game-list/{id}/update-image-url', [GameListController::class, 'updateImageUrl'])->name('game_list.update_image_url');
     Route::get('game-list-order/{gameList}/edit', [GameListController::class, 'GameListOrderedit'])->name('game_list_order.edit');
     Route::post('/game-lists/{id}/update-order', [GameListController::class, 'updateOrder'])->name('GameListOrderUpdate');
 
     // Buffalo game list (FugoGameList) start
     Route::get('buffalo-game-lists', [FugoGameListController::class, 'index'])->name('fugoGameLists.index');
     Route::patch('buffalo-game-lists/{id}/toggle-hot-status', [FugoGameListController::class, 'toggleHotStatus'])->name('fugoGameLists.toggleHotStatus');
     Route::patch('buffalo-game-lists/{id}/toggle-status', [FugoGameListController::class, 'toggleStatus'])->name('fugoGameLists.toggleStatus');
     // Buffalo game list end

     // wager list start
     Route::get('wager-list', [WagerListController::class, 'index'])->name('wager-list.index');
     Route::post('wager-list/fetch', [WagerListController::class, 'fetch'])->name('wager-list.fetch');
     Route::get('wager-list/wallet-balance', [WagerListController::class, 'walletBalance'])->name('wager-list.wallet-balance');
     Route::get('wager-list/{wagerCode}/game-history', [WagerListController::class, 'gameHistory'])->name('wager-list.game-history');
     Route::get('wager-list/{idOrCode}', [WagerListController::class, 'show'])->name('wager-list.show');
     // wager list end
 
    
});
