<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $userId   = auth()->id();
        $endpoint = $validated['endpoint'];

        // Detectar tipo (apple ou fcm) e remover subscriptions antigas do mesmo tipo
        $isApple = str_contains($endpoint, 'web.push.apple.com');
        PushSubscription::where('user_id', $userId)
            ->where('endpoint', $isApple ? 'like' : 'not like', '%web.push.apple.com%')
            ->where('endpoint', '!=', $endpoint)
            ->delete();

        PushSubscription::updateOrCreate(
            [
                'user_id'       => $userId,
                'endpoint_hash' => hash('sha256', $endpoint),
            ],
            [
                'endpoint' => $endpoint,
                'p256dh'   => $validated['keys']['p256dh'],
                'auth'     => $validated['keys']['auth'],
            ]
        );

        return response()->json(['success' => true]);
    }

    public function unsubscribe(Request $request)
    {
        PushSubscription::where('user_id', auth()->id())
            ->where('endpoint_hash', hash('sha256', $request->input('endpoint')))
            ->delete();

        return response()->json(['success' => true]);
    }
}
