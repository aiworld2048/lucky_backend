<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FugoGameList;
use Illuminate\Http\Request;

class FugoGameListController extends Controller
{
    /**
     * Display a listing of Buffalo games with pagination.
     */
    public function index()
    {
        $fugo_game_lists = FugoGameList::with('fugoProvider')
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(20);

        return view('admin.fugo_game_list.index', compact('fugo_game_lists'));
    }

    /**
     * Toggle hot status of a Buffalo game.
     */
    public function toggleHotStatus($id)
    {
        $game = FugoGameList::findOrFail($id);
        $game->hot_status = $game->hot_status == 1 ? 0 : 1;
        $game->save();

        return redirect()->route('admin.fugoGameLists.index')
            ->with('success', 'Hot game status updated successfully.');
    }

    /**
     * Toggle status of a Buffalo game.
     */
    public function toggleStatus($id)
    {
        $game = FugoGameList::findOrFail($id);
        $game->status = $game->status == 1 ? 0 : 1;
        $game->save();

        return redirect()->route('admin.fugoGameLists.index')
            ->with('success', 'Game status updated successfully.');
    }
}

