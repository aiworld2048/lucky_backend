<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function unread(Request $request)
    {
        $user = $request->user();

        $notifications = $user->unreadNotifications()
            ->latest()
            ->take(100)
            ->get();

        $deposit = $notifications->filter(fn ($notification) => ($notification->data['type'] ?? 'deposit') === 'deposit');
        $withdraw = $notifications->filter(fn ($notification) => ($notification->data['type'] ?? '') === 'withdraw');

        return response()->json([
            'notifications' => [
                'deposit' => $this->transform($deposit),
                'withdraw' => $this->transform($withdraw),
            ],
            'counts' => [
                'deposit' => $deposit->count(),
                'withdraw' => $withdraw->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string'],
        ]);

        $user = $request->user();
        $notifications = $user->unreadNotifications()
            ->whereIn('id', $validated['ids'])
            ->get();

        foreach ($notifications as $notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'marked' => $notifications->pluck('id'),
            'remaining' => $user->unreadNotifications()->count(),
        ]);
    }

    private function transform($notifications)
    {
        return $notifications->values()->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? 'deposit',
                'player_name' => $notification->data['player_name'] ?? '',
                'amount' => $notification->data['amount'] ?? null,
                'message' => $notification->data['message'] ?? '',
                'created_at_human' => $notification->created_at->diffForHumans(),
                'route' => $notification->data['route'] ?? '#',
                'request_id' => $notification->data['deposit_request_id']
                    ?? $notification->data['withdraw_request_id']
                    ?? null,
            ];
        });
    }
}

