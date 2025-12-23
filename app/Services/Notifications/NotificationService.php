<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationService
{
    public function send(User $user, Notification $notification): void
    {
        // If notification exposes a fingerprint, use it to upsert and avoid duplicates
        $fingerprint = method_exists($notification, 'fingerprint')
            ? $notification->fingerprint()
            : null;

        if ($fingerprint !== null) {
            $payload = method_exists($notification, 'toDatabase')
                ? $notification->toDatabase($user)
                : [];

            // Ensure fingerprint stored for querying
            $payload['fingerprint'] = $payload['fingerprint'] ?? $fingerprint;

            // Check if notification already exists
            $existing = DB::table('notifications')
                ->where('notifiable_id', $user->getKey())
                ->where('notifiable_type', $user->getMorphClass())
                ->where('type', $notification::class)
                ->where('fingerprint', $fingerprint)
                ->exists();

            if (!$existing) {
                DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(),
                    'notifiable_id' => $user->getKey(),
                    'notifiable_type' => $user->getMorphClass(),
                    'type' => $notification::class,
                    'fingerprint' => $fingerprint,
                    'data' => json_encode($payload),
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return;
        }

        $user->notify($notification);
    }
}
