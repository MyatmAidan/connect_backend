<?php

namespace App\Services;

use App\Enums\ConnectionRequestStatus;
use App\Models\Connection;
use App\Models\ConnectionRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Contracts\ConnectionRequestRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConnectionRequestService
{
    public function __construct(
        private readonly ConnectionRequestRepositoryInterface $requests,
        private readonly UserNotificationService $notifications,
    ) {
    }

    public function list(array $filters, int $perPage = 15)
    {
        return $this->requests->paginate($filters, $perPage);
    }

    public function store(User $sender, array $data): ConnectionRequest
    {
        if ($sender->id === $data['receiver_id']) {
            throw ValidationException::withMessages(['receiver_id' => ['You cannot connect with yourself.']]);
        }

        $existingConnection = Connection::query()
            ->where(function ($query) use ($sender, $data) {
                $query->where('user_one_id', $sender->id)
                    ->where('user_two_id', $data['receiver_id']);
            })
            ->orWhere(function ($query) use ($sender, $data) {
                $query->where('user_one_id', $data['receiver_id'])
                    ->where('user_two_id', $sender->id);
            })
            ->exists();

        if ($existingConnection) {
            throw ValidationException::withMessages(['receiver_id' => ['You are already connected with this developer.']]);
        }

        $pending = ConnectionRequest::query()
            ->where('status', ConnectionRequestStatus::Pending->value)
            ->where(function ($query) use ($sender, $data) {
                $query->where('sender_id', $sender->id)
                    ->where('receiver_id', $data['receiver_id']);
            })
            ->orWhere(function ($query) use ($sender, $data) {
                $query->where('sender_id', $data['receiver_id'])
                    ->where('receiver_id', $sender->id);
            })
            ->first();

        if ($pending) {
            $message = $pending->sender_id === $sender->id
                ? 'Friend request already sent.'
                : 'This developer already sent you a friend request. Check your requests.';

            throw ValidationException::withMessages(['receiver_id' => [$message]]);
        }

        $request = $this->requests->create([
            'sender_id' => $sender->id,
            'receiver_id' => $data['receiver_id'],
            'message' => $data['message'] ?? null,
            'status' => ConnectionRequestStatus::Pending->value,
        ])->load(['sender', 'receiver']);

        $this->notifications->connectionRequestReceived($request);

        return $request;
    }

    public function accept(ConnectionRequest $request, User $user): Connection
    {
        $this->assertReceiver($request, $user);

        $connection = DB::transaction(function () use ($request) {
            $this->requests->update($request, ['status' => ConnectionRequestStatus::Accepted->value]);

            [$userOne, $userTwo] = $this->orderedPair($request->sender_id, $request->receiver_id);

            $connection = Connection::query()->create([
                'user_one_id' => $userOne,
                'user_two_id' => $userTwo,
                'connection_request_id' => $request->id,
            ]);

            Conversation::query()->create(['connection_id' => $connection->id]);

            return $connection->load(['userOne', 'userTwo', 'conversation']);
        });

        $this->notifications->connectionRequestAccepted($request);

        return $connection;
    }

    public function reject(ConnectionRequest $request, User $user): ConnectionRequest
    {
        $this->assertReceiver($request, $user);
        $this->requests->update($request, ['status' => ConnectionRequestStatus::Rejected->value]);

        return $request->fresh(['sender', 'receiver']);
    }

    public function cancel(ConnectionRequest $request, User $user): ConnectionRequest
    {
        if ($request->sender_id !== $user->id) {
            throw ValidationException::withMessages(['request' => ['Only the sender can cancel this request.']]);
        }

        $this->requests->update($request, ['status' => ConnectionRequestStatus::Cancelled->value]);

        return $request->fresh(['sender', 'receiver']);
    }

    private function assertReceiver(ConnectionRequest $request, User $user): void
    {
        if ($request->receiver_id !== $user->id) {
            throw ValidationException::withMessages(['request' => ['You are not allowed to perform this action.']]);
        }
    }

    private function orderedPair(string $a, string $b): array
    {
        return strcmp($a, $b) < 0 ? [$a, $b] : [$b, $a];
    }
}
