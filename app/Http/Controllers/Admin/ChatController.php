<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\Notification\ChatSocketNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function __construct(private ChatSocketNotifier $chatNotifier)
    {
    }

    public function index()
    {
        $agent = Auth::user();
        $this->ensureAgent($agent);

        $players = $this->playersForAgent($agent);

        return view('admin.chat.index', [
            'players' => $players,
        ]);
    }

    public function messages(Request $request, User $player)
    {
        $agent = Auth::user();
        $this->ensurePlayerBelongsToAgent($agent, $player);

        $perPage = (int) $request->integer('per_page', 50);

        $messages = ChatMessage::query()
            ->forParticipants($agent->id, $player->id)
            ->latest('id')
            ->paginate($perPage);

        ChatMessage::query()
            ->forParticipants($agent->id, $player->id)
            ->whereNull('read_at')
            ->where('receiver_id', $agent->id)
            ->update(['read_at' => now()]);

        $messages->getCollection()->load('sender');

        return ChatMessageResource::collection($messages);
    }

    public function store(Request $request, User $player)
    {
        $agent = Auth::user();
        $this->ensurePlayerBelongsToAgent($agent, $player);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $messageBody = trim($validated['message']);

        if ($messageBody === '') {
            return $this->responseError('Message cannot be empty.', $request);
        }

        $message = ChatMessage::create([
            'agent_id' => $agent->id,
            'player_id' => $player->id,
            'sender_id' => $agent->id,
            'receiver_id' => $player->id,
            'sender_type' => ChatMessage::SENDER_AGENT,
            'message' => $messageBody,
        ]);

        $message->load('sender');

        $this->chatNotifier->notify($message);

        if ($request->wantsJson()) {
            return (new ChatMessageResource($message))
                ->response()
                ->setStatusCode(201);
        }

        return back()->with('success', 'Message sent.');
    }

    private function ensureAgent(User $user): void
    {
        abort_unless((int) $user->type === UserType::Agent->value, 403, 'Chat is available for agents only.');
    }

    private function ensurePlayerBelongsToAgent(User $agent, User $player): void
    {
        $this->ensureAgent($agent);

        abort_unless((int) $player->agent_id === (int) $agent->id, 403, 'Player does not belong to this agent.');
    }

    /**
     * @return Collection<int, User>
     */
    private function playersForAgent(User $agent): Collection
    {
        $players = User::query()
            ->select('id', 'name', 'user_name', 'phone')
            ->where('agent_id', $agent->id)
            ->where('type', UserType::Player->value)
            ->orderBy('user_name')
            ->get();

        $unreadCounts = ChatMessage::query()
            ->select('player_id', DB::raw('COUNT(*) as unread_count'))
            ->where('agent_id', $agent->id)
            ->where('receiver_id', $agent->id)
            ->whereNull('read_at')
            ->groupBy('player_id')
            ->pluck('unread_count', 'player_id');

        return $players->map(function (User $player) use ($unreadCounts) {
            $player->unread_chat_count = (int) ($unreadCounts[$player->id] ?? 0);

            return $player;
        });
    }

    private function responseError(string $message, Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => $message,
            ], 422);
        }

        return back()->withErrors($message);
    }
}

