<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Mobile\Chat\StoreMessageRequest;
use App\Http\Requests\Api\V1\Mobile\Chat\UpdateMessageRequest;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\MessageService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MobileMessageController extends Controller
{
    public function __construct(private readonly MessageService $messages)
    {
    }

    public function index(Conversation $conversation, Request $request)
    {
        $paginator = $this->messages->list($conversation, $request->user(), (int) $request->get('per_page', 30));

        return ApiResponse::paginated($paginator, MessageResource::collection($paginator)->resolve());
    }

    public function store(Conversation $conversation, StoreMessageRequest $request)
    {
        $message = $this->messages->store($conversation, $request->user(), $request->validated());

        return ApiResponse::success(new MessageResource($message), 'Message sent.', 201);
    }

    public function update(Conversation $conversation, Message $message, UpdateMessageRequest $request)
    {
        $updated = $this->messages->update($conversation, $message, $request->user(), $request->validated());

        return ApiResponse::success(new MessageResource($updated), 'Message updated.');
    }

    public function destroy(Conversation $conversation, Message $message, Request $request)
    {
        $deleted = $this->messages->delete($conversation, $message, $request->user());

        return ApiResponse::success(new MessageResource($deleted), 'Message deleted.');
    }

    public function pin(Conversation $conversation, Message $message, Request $request)
    {
        $pinned = $this->messages->pin($conversation, $message, $request->user());

        return ApiResponse::success(new MessageResource($pinned), 'Message pinned.');
    }

    public function unpin(Conversation $conversation, Message $message, Request $request)
    {
        $unpinned = $this->messages->unpin($conversation, $message, $request->user());

        return ApiResponse::success(new MessageResource($unpinned), 'Message unpinned.');
    }

    public function markAsRead(Conversation $conversation, Request $request)
    {
        $count = $this->messages->markAsRead($conversation, $request->user());

        return ApiResponse::success(['marked' => $count], 'Messages marked as read.');
    }
}
