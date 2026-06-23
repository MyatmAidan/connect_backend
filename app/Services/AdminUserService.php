<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class AdminUserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AdminLogService $adminLogs,
    ) {
    }

    public function list(array $filters, int $perPage = 15)
    {
        return $this->users->paginate($filters, $perPage);
    }

    public function update(User $user, array $data, User $admin): User
    {
        $updated = $this->users->update($user, $data);
        $this->adminLogs->log($admin, 'update_user', User::class, $user->id, 'Updated user '.$user->id);

        return $updated;
    }

    public function ban(User $user, User $admin): User
    {
        $updated = $this->users->update($user, ['status' => UserStatus::Banned->value]);
        $this->adminLogs->log($admin, 'ban_user', User::class, $user->id, 'Banned user '.$user->id);

        return $updated;
    }

    public function unban(User $user, User $admin): User
    {
        $updated = $this->users->update($user, ['status' => UserStatus::Active->value]);
        $this->adminLogs->log($admin, 'unban_user', User::class, $user->id, 'Unbanned user '.$user->id);

        return $updated;
    }

    public function delete(User $user, User $admin): void
    {
        $this->adminLogs->log($admin, 'delete_user', User::class, $user->id, 'Deleted user '.$user->id);
        $this->users->delete($user);
    }
}
