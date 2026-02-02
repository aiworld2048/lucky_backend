<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class GameListController extends Controller
{
    // get game list with pagination 20
    public function GetGameList()
    {
        // $game_lists = GameList::paginate(20);
        $game_lists = GameList::orderBy('id', 'asc')->paginate(20);

        return view('admin.game_list.paginate_index', compact('game_lists'));
    }

    // public function GetGameList()
    // {
    //     $games = GameList::query()
    //         ->with(['gameType', 'product'])
    //         ->orderBy('order')
    //         ->orderBy('id')
    //         ->paginate(20);

    //     return view('admin.game_list.index', compact('games'));
    // }

    

    public function toggleStatus($id)
    {
        $game = GameList::findOrFail($id);
        $game->status = $game->status == 1 ? 0 : 1;
        $game->save();

        return redirect()->route('admin.gameLists.index')->with('success', 'Game status updated successfully.');
    }

    public function HotGameStatus($id)
    {
        $game = GameList::findOrFail($id);
        $game->hot_status = $game->hot_status == 1 ? 0 : 1;
        $game->save();

        return redirect()->route('admin.gameLists.index')->with('success', 'HotGame status updated successfully.');
    }

    public function PPHotGameStatus($id)
    {
        $game = GameList::findOrFail($id);
        $game->pp_hot = $game->pp_hot == 1 ? 0 : 1;
        $game->save();

        return redirect()->route('admin.gameLists.index')->with('success', 'PP HotGame status updated successfully.');
    }

    public function GameListOrderedit(GameList $gameList)
    {
        return view('admin.game_list.order_edit', compact('gameList'));
    }

    public function updateOrder(Request $request, $id)
    {
        $request->validate([
            'order' => 'required|integer|min:0',
        ]);

        $gameList = GameList::findOrFail($id);

        $gameList->order = $request->input('order');
        $gameList->save();

        return redirect()->route('admin.gameLists.index')->with('success', 'Game list order  updated successfully.');
    }

    public function updateAllOrder(Request $request)
    {
        $request->validate([
            'order' => 'required|integer',
        ]);

        $newOrderValue = $request->input('order');

        $updatedCount = GameList::query()->update(['order' => $newOrderValue]);

        return redirect()
            ->back()
            ->with('success', "Order column updated for all rows successfully. Updated rows: $updatedCount.");
    }

    /**
     * Update the image_url for a specific game.
     */
    public function edit(GameList $gameList)
    {
        return view('admin.game_list.edit', compact('gameList'));
    }

    public function updateImageUrl(Request $request, $id)
    {
        $game = GameList::findOrFail($id);

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $image = $request->file('image');

        if ($image && $image->isValid()) {
            $filename = $image->getClientOriginalName();

            $image->move(public_path('assets/img/game_list/'), $filename);

            $game->update([
                'image_url' => 'https://ponewine20x.xyz/assets/img/game_list/'.$filename,
            ]);

            return redirect()->route('admin.gameLists.index')->with('success', 'Image updated successfully.');
        }

        return redirect()->back()->withErrors('File upload failed.');
    }
}
