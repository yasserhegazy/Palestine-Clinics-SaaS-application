<?php

namespace App\Http\Controllers;

use App\Http\Resources\Notification\NotificationResource;
use App\Services\Notifications\NotificationQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationQueryService $notifications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $perPage = (int) $request->query('per_page', 20);

        $paginator = $this->notifications->list($request->user(), $status, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully.',
            'data' => NotificationResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function markRead(Request $request, string $notificationId): JsonResponse
    {
        $this->notifications->markRead($request->user(), $notificationId);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $updated = $this->notifications->markAllRead($request->user());

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
            'data' => ['updated' => $updated],
        ]);
    }
}
