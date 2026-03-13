<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\WatchSession;
use Illuminate\Http\Request;

class WatchSessionController extends Controller
{
    /**
     * Return the number of active sessions for a given eventId.
     */
    public function activeCount($eventId)
    {
        $count = WatchSession::where('eventId', $eventId)
            ->where('status', 'active')
            ->count();

        return response()->json([
            'eventId' => $eventId,
            'activeSessions' => $count,
        ]);
    }

    /**
     * Return session details for a given sessionId.
     */
    public function show($sessionId)
    {
        $session = WatchSession::where('sessionId', $sessionId)->first();
        $eventsForSession = Event::where('sessionId', $sessionId)->count();

        if (!$session) {
            return response()->json([
                'message' => 'Session not found',
            ], 404);
        }

        return response()->json([
            'session' => $session,
            'eventsReceivedForSession' => $eventsForSession,
        ]);
    }
}