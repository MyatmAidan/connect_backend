<?php

namespace App\Services;

use App\Enums\ConnectionRequestStatus;
use App\Models\Connection;
use App\Models\ConnectionRequest;
use App\Models\User;
use Illuminate\Support\Collection;

class ConnectionStatusService
{
    public function between(User $viewer, string $targetUserId): array
    {
        return $this->mapForViewer($viewer, [$targetUserId])[$targetUserId];
    }

    /**
     * @param  list<string>  $targetUserIds
     * @return array<string, array<string, mixed>>
     */
    public function mapForViewer(User $viewer, array $targetUserIds): array
    {
        $targetUserIds = array_values(array_unique(array_filter($targetUserIds)));

        $result = [];
        foreach ($targetUserIds as $targetUserId) {
            if ($viewer->id === $targetUserId) {
                $result[$targetUserId] = ['connection_status' => 'self'];
                continue;
            }

            $result[$targetUserId] = ['connection_status' => 'none'];
        }

        if ($targetUserIds === []) {
            return $result;
        }

        $connections = Connection::query()
            ->with('conversation')
            ->where(function ($query) use ($viewer, $targetUserIds) {
                $query->where('user_one_id', $viewer->id)
                    ->whereIn('user_two_id', $targetUserIds);
            })
            ->orWhere(function ($query) use ($viewer, $targetUserIds) {
                $query->where('user_two_id', $viewer->id)
                    ->whereIn('user_one_id', $targetUserIds);
            })
            ->get();

        foreach ($connections as $connection) {
            $otherUserId = $connection->user_one_id === $viewer->id
                ? $connection->user_two_id
                : $connection->user_one_id;

            $result[$otherUserId] = [
                'connection_status' => 'connected',
                'connection_id' => $connection->id,
                'conversation_id' => $connection->conversation?->id,
            ];
        }

        $pendingUserIds = collect($targetUserIds)
            ->reject(fn (string $id) => ($result[$id]['connection_status'] ?? 'none') === 'connected')
            ->values()
            ->all();

        if ($pendingUserIds === []) {
            return $result;
        }

        $requests = ConnectionRequest::query()
            ->where('status', ConnectionRequestStatus::Pending->value)
            ->where(function ($query) use ($viewer, $pendingUserIds) {
                $query->where('sender_id', $viewer->id)
                    ->whereIn('receiver_id', $pendingUserIds);
            })
            ->orWhere(function ($query) use ($viewer, $pendingUserIds) {
                $query->where('receiver_id', $viewer->id)
                    ->whereIn('sender_id', $pendingUserIds);
            })
            ->get();

        foreach ($requests as $request) {
            if ($request->sender_id === $viewer->id) {
                $result[$request->receiver_id] = [
                    'connection_status' => 'pending_sent',
                    'connection_request_id' => $request->id,
                ];
                continue;
            }

            $result[$request->sender_id] = [
                'connection_status' => 'pending_received',
                'connection_request_id' => $request->id,
            ];
        }

        return $result;
    }

    public function enrichProfiles(User $viewer, Collection $profiles): Collection
    {
        $statusMap = $this->mapForViewer(
            $viewer,
            $profiles->pluck('user_id')->filter()->all(),
        );

        return $profiles->map(function ($profile) use ($statusMap) {
            $profile->setAttribute(
                'connection_meta',
                $statusMap[$profile->user_id] ?? ['connection_status' => 'none'],
            );

            return $profile;
        });
    }
}
