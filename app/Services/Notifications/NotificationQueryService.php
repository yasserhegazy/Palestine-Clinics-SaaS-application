<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationQueryService
{
    public function list(User $user, ?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = $user->notifications()->orderByDesc('created_at');

        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        return $query->paginate($perPage);
    }

    public function markRead(User $user, string $notificationId): void
    {
        $notification = $user->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->markAsRead();
    }

    public function markAllRead(User $user): int
    {
        return $user->unreadNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function latest(User $user, int $limit = 10): Collection
    {
        return $user->notifications()->orderByDesc('created_at')->limit($limit)->get();
    }
}
