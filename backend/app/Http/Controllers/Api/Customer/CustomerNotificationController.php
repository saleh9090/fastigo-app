<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class CustomerNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::query()
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Notification $notification): array => $this->formatNotification($notification));

        return response()->json([
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        if ((int) $notification->customer_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'This notification does not belong to the authenticated customer.',
            ], 403);
        }

        $notification->update([
            'is_read' => true,
        ]);

        return response()->json([
            'notification' => $this->formatNotification($notification),
        ]);
    }

    private function formatNotification(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'bill_id' => $notification->bill_id,
            'title' => $notification->title,
            'message' => $notification->message,
            'is_read' => $notification->is_read,
            'created_at' => $notification->created_at,
        ];
    }
}
