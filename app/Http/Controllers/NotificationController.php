<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
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
