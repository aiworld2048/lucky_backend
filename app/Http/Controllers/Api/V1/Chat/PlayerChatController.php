<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Services\Notification\ChatSocketNotifier;
use Illuminate\Http\Request;

class PlayerChatController extends Controller
{
    public function __construct(private ChatSocketNotifier $chatNotifier)
    {
    }

    public function index(Request $request)
    {
        $player = $request->user();

        if (! $player->agent_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'No agent is associated with your account.',
            ], 422);
        }

        $perPage = (int) $request->integer('per_page', 5);

        $messages = ChatMessage::query()
            ->forParticipants($player->agent_id, $player->id)
            ->latest('id')
            ->paginate($perPage);

        ChatMessage::query()
            ->forParticipants($player->agent_id, $player->id)
            ->whereNull('read_at')
            ->where('receiver_id', $player->id)
            ->update(['read_at' => now()]);

        $messages->getCollection()->load('sender');

        return ChatMessageResource::collection($messages);
    }

    public function store(Request $request)
    {
        $player = $request->user();

        if (! $player->agent_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'No agent is associated with your account.',
            ], 422);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $messageBody = trim($validated['message']);

        if ($messageBody === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Message cannot be empty.',
            ], 422);
        }

        $message = ChatMessage::create([
            'agent_id' => $player->agent_id,
            'player_id' => $player->id,
            'sender_id' => $player->id,
            'receiver_id' => $player->agent_id,
            'sender_type' => ChatMessage::SENDER_PLAYER,
            'message' => $messageBody,
        ]);

        $message->load('sender');

        $this->chatNotifier->notify($message);

        return (new ChatMessageResource($message))
            ->response()
            ->setStatusCode(201);
    }
}

