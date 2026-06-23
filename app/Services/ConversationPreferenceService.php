<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationUserSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConversationPreferenceService
{
    public function pin(Conversation $conversation, User $user): ConversationUserSetting
    {
        $this->assertParticipant($conversation, $user);

        return DB::transaction(function () use ($conversation, $user) {
            $setting = $this->getOrCreate($conversation, $user);

            if ($setting->is_pinned) {
                return $setting;
            }

            $nextOrder = ((int) ConversationUserSetting::query()
                ->where('user_id', $user->id)
                ->where('is_pinned', true)
                ->max('pin_order')) + 1;

            $setting->update([
                'is_pinned' => true,
                'pin_order' => $nextOrder,
                'hidden_at' => null,
            ]);

            return $setting->fresh();
        });
    }

    public function unpin(Conversation $conversation, User $user): ConversationUserSetting
    {
        $this->assertParticipant($conversation, $user);

        $setting = $this->getOrCreate($conversation, $user);
        $setting->update([
            'is_pinned' => false,
            'pin_order' => null,
        ]);

        return $setting->fresh();
    }

    public function reorderPinned(User $user, array $ids): void
    {
        DB::transaction(function () use ($user, $ids) {
            $settings = ConversationUserSetting::query()
                ->where('user_id', $user->id)
                ->where('is_pinned', true)
                ->whereIn('conversation_id', $ids)
                ->get()
                ->keyBy('conversation_id');

            if ($settings->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'ids' => ['Only pinned conversations can be reordered.'],
                ]);
            }

            foreach ($ids as $index => $conversationId) {
                $settings[$conversationId]->update(['pin_order' => $index + 1]);
            }
        });
    }

    public function hide(Conversation $conversation, User $user): void
    {
        $this->assertParticipant($conversation, $user);

        $setting = $this->getOrCreate($conversation, $user);
        $setting->update([
            'is_pinned' => false,
            'pin_order' => null,
            'hidden_at' => now(),
        ]);
    }

    public function mute(Conversation $conversation, User $user): ConversationUserSetting
    {
        $this->assertParticipant($conversation, $user);

        $setting = $this->getOrCreate($conversation, $user);
        $setting->update(['is_muted' => true, 'hidden_at' => null]);

        return $setting->fresh();
    }

    public function unmute(Conversation $conversation, User $user): ConversationUserSetting
    {
        $this->assertParticipant($conversation, $user);

        $setting = $this->getOrCreate($conversation, $user);
        $setting->update(['is_muted' => false]);

        return $setting->fresh();
    }

    private function getOrCreate(Conversation $conversation, User $user): ConversationUserSetting
    {
        return ConversationUserSetting::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
            ],
            [
                'is_pinned' => false,
                'pin_order' => null,
                'is_muted' => false,
                'hidden_at' => null,
            ],
        );
    }

    private function assertParticipant(Conversation $conversation, User $user): void
    {
        $connection = $conversation->connection;
        if (! in_array($user->id, [$connection->user_one_id, $connection->user_two_id], true)) {
            throw ValidationException::withMessages([
                'conversation' => ['You do not have access to this conversation.'],
            ]);
        }
    }
}
