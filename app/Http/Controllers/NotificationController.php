<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * JSON controller backing the in-app notification bell.
 *
 * Endpoints:
 *   GET  /notifications             → list the 20 most-recent rows for the current user.
 *   GET  /notifications/unread-count → count of `read_at IS NULL` rows.
 *   POST /notifications/{id}/read   → mark a single row as read.
 *   POST /notifications/read-all    → mark every unread row as read.
 */
class NotificationController extends Controller
{
    /**
     * Return the 20 most-recent notifications owned by the authenticated user.
     *
     * Newest first; each row is shaped for direct consumption by the
     * bell dropdown (id, type, title, body, link, read_at ISO timestamp).
     *
     * @return JsonResponse JSON `{"data": [...], "meta": {...}}` payload.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $paginator = Notification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->respondWithPaginator($paginator);
    }

    /**
     * Return the count of `read_at IS NULL` rows for the authenticated user.
     *
     * Designed to be polled cheaply every 60s.
     */
    public function unreadCount(): JsonResponse
    {
        $count = Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark a single notification as read.
     *
     * Refuses with 404 when the row is not owned by the current user,
     * so the route cannot be used to probe other users' notifications.
     */
    public function markRead(Notification $notification): JsonResponse
    {
        $this->authorizeOwnership($notification);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => Carbon::now()]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Mark every unread notification owned by the current user as read.
     */
    public function markAllRead(): JsonResponse
    {
        $updated = Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return response()->json(['ok' => true, 'updated' => $updated]);
    }

    /**
     * Shape a `LengthAwarePaginator` into the JSON envelope expected by
     * the frontend bell (flat `data` array + minimal `meta`).
     *
     * @param  LengthAwarePaginator<Notification>  $paginator  Eloquent paginator over Notification rows.
     * @return JsonResponse The JSON response.
     */
    private function respondWithPaginator(LengthAwarePaginator $paginator): JsonResponse
    {
        $data = $paginator->getCollection()->map(fn (Notification $n) => [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'body' => $n->body,
            'link' => $n->link,
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at?->toIso8601String(),
        ])->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Refuse access when the notification is not owned by the
     * authenticated user. 404 (not 403) is used so the existence of
     * other users' rows is not leaked.
     */
    private function authorizeOwnership(Notification $notification): void
    {
        if ($notification->user_id !== auth()->id()) {
            abort(404);
        }
    }
}
