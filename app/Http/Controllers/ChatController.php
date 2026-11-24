<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatRequest;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;

final class ChatController extends Controller
{
    public function __invoke(ChatRequest $request, ChatService $chat): JsonResponse
    {
        $data = $request->validated();
        $firstName = $request->user()?->name ?? $request->session()->get('wizard.first_name');
        $reply = $chat->handle(app()->getLocale(), $data['procedures'], $data['message'], $data['history'] ?? [], $firstName);

        return response()->json([
            'reply' => $reply->text,
            'red_flag' => $reply->red_flag,
            'fallback' => $reply->fallback,
        ])->header('Cache-Control', 'no-store, private');
    }
}
