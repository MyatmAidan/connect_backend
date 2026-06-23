<?php

namespace App\Repositories\Eloquent;

use App\Models\Conversation;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class ConversationRepository extends BaseRepository implements ConversationRepositoryInterface
{
    public function __construct(Conversation $model)
    {
        parent::__construct($model);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $userId = $filters['user_id'] ?? null;

        $query->with([
            'connection.userOne.developerProfile',
            'connection.userTwo.developerProfile',
            'latestMessage.sender',
            'userSettings' => function ($settingsQuery) use ($userId) {
                if ($userId) {
                    $settingsQuery->where('user_id', $userId);
                }
            },
        ]);

        if ($userId) {
            $query->whereHas('connection', function (Builder $q) use ($userId) {
                $q->where('user_one_id', $userId)->orWhere('user_two_id', $userId);
            });

            $query->whereDoesntHave('userSettings', function (Builder $q) use ($userId) {
                $q->where('user_id', $userId)->whereNotNull('hidden_at');
            });

            $query->leftJoin('conversation_user_settings as conversation_preferences', function ($join) use ($userId) {
                $join->on('conversations.id', '=', 'conversation_preferences.conversation_id')
                    ->where('conversation_preferences.user_id', '=', $userId);
            });

            $query->select('conversations.*')
                ->orderByRaw('COALESCE(conversation_preferences.is_pinned, 0) DESC')
                ->orderByRaw('CASE WHEN conversation_preferences.pin_order IS NULL THEN 1 ELSE 0 END')
                ->orderBy('conversation_preferences.pin_order')
                ->orderByDesc('conversations.last_message_at');
        } else {
            $query->orderByDesc('last_message_at');
        }

        return $query;
    }
}
