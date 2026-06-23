<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Mobile\Conversation\ReorderPinnedConversationsRequest;
use App\Http\Resources\Api\V1\ConversationResource;
use App\Models\Conversation;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Services\ConversationPreferenceService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MobileConversationController extends Controller
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
        private readonly ConversationPreferenceService $preferences,
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->conversations->paginate(
            ['user_id' => $request->user()->id],
            (int) $request->get('per_page', 15)
        );

        return ApiResponse::paginated($paginator, ConversationResource::collection($paginator)->resolve());
    }

    public function show(Conversation $conversation, Request $request)
    {
        $this->authorizeParticipant($conversation, $request);

        return ApiResponse::success(new ConversationResource(
            $conversation->load([
                'connection.userOne.developerProfile',
                'connection.userTwo.developerProfile',
                'latestMessage.sender',
                'userSettings' => fn ($query) => $query->where('user_id', $request->user()->id),
            ])
        ));
    }

    public function pin(Conversation $conversation, Request $request)
    {
        $this->authorizeParticipant($conversation, $request);
        $this->preferences->pin($conversation, $request->user());

        return ApiResponse::success(null, 'Conversation pinned.');
    }

    public function unpin(Conversation $conversation, Request $request)
    {
        $this->authorizeParticipant($conversation, $request);
        $this->preferences->unpin($conversation, $request->user());

        return ApiResponse::success(null, 'Conversation unpinned.');
    }

    public function reorderPinned(ReorderPinnedConversationsRequest $request)
    {
        $this->preferences->reorderPinned($request->user(), $request->validated('ids'));

        return ApiResponse::success(null, 'Pinned conversations reordered.');
    }

    public function destroy(Conversation $conversation, Request $request)
    {
        $this->authorizeParticipant($conversation, $request);
        $this->preferences->hide($conversation, $request->user());

        return ApiResponse::success(null, 'Conversation removed.');
    }

    public function mute(Conversation $conversation, Request $request)
    {
        $this->authorizeParticipant($conversation, $request);
        $this->preferences->mute($conversation, $request->user());

        return ApiResponse::success(null, 'Conversation muted.');
    }

    public function unmute(Conversation $conversation, Request $request)
    {
        $this->authorizeParticipant($conversation, $request);
        $this->preferences->unmute($conversation, $request->user());

        return ApiResponse::success(null, 'Conversation unmuted.');
    }

    private function authorizeParticipant(Conversation $conversation, Request $request): void
    {
        $connection = $conversation->connection;
        $userId = $request->user()->id;
        if (! in_array($userId, [$connection->user_one_id, $connection->user_two_id], true)) {
            throw ValidationException::withMessages(['conversation' => ['Unauthorized.']]);
        }
    }
}
