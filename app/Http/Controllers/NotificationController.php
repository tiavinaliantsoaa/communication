<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function poll(Request $request)
    {
        $userId = $request->user()->id;

        $latest = UserNotification::where('user_id', $userId)
            ->orderByDesc('id')
            ->first(['id', 'titre', 'created_at']);

        $unread = UserNotification::where('user_id', $userId)->unread()->count();
        $total = UserNotification::where('user_id', $userId)->count();

        return response()->json([
            'ok' => true,
            'total' => $total,
            'unread' => $unread,
            'latest_id' => (int) ($latest?->id ?? 0),
            'latest_titre' => $latest?->titre,
        ]);
    }

    public function markRead(Request $request)
    {
        UserNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, UserNotification $notification)
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        $notification->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Notification supprimée.');
    }

    public function destroyAll(Request $request)
    {
        UserNotification::where('user_id', $request->user()->id)->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Toutes les notifications personnelles ont été supprimées.');
    }
}
