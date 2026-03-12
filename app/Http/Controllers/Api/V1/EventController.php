<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Carbon\Carbon;

class EventController extends Controller
{
    /**
     * Ingestion endpoint for v1 of incoming event heartbeats
     * POST /v1/events
     */
    public function ingest(Request $request)
    {
        $validated = $request->validate([
            'sessionId' => 'required|string',
            'userId' => 'required|string',
            'eventType' => 'required|string',
            'eventId' => 'required|string',
            'eventTimestamp' => 'required|date',
            'receivedAt' => 'required|date',
            'payload' => 'nullable|array',
        ]);

        Event::create([
            'session_id' => $validated['sessionId'],
            'user_id' => $validated['userId'],
            'event_type' => $validated['eventType'],
            'event_timestamp' => $validated['eventTimestamp'],
            'payload' => $validated['payload'] ?? [],
        ]);

        return response()->json(['status' => 'ok'], 201);
    }

    /**
     * Get active session count for a specific event.
     * GET /v1/events/{eventId}/active-sessions
     */
    public function activeSessions($eventId)
    {
        $window = Carbon::now()->subSeconds(45);

        $activeSessions = Event::where('payload->eventId', $eventId)
            ->where('event_type', 'heartbeat')
            ->where('event_timestamp', '>=', $window)
            ->groupBy('session_id')
            ->get()
            ->count();

        return response()->json([
            'eventId' => $eventId,
            'activeSessions' => $activeSessions
        ]);
    }

    /**
     * Get all events for a specific session.
     * GET /v1/sessions/{sessionId}
     */
    public function sessionDetails($sessionId)
    {
        $events = Event::where('session_id', $sessionId)
            ->orderBy('event_timestamp')
            ->get();

        if ($events->isEmpty()) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $firstEvent = $events->first();
        $lastEvent = $events->last();

        return response()->json([
            'sessionId' => $sessionId,
            'userId' => $firstEvent->user_id,
            'eventId' => $firstEvent->payload['eventId'] ?? null,
            'durationSeconds' => Carbon::parse($firstEvent->event_timestamp)
                                    ->diffInSeconds(Carbon::parse($lastEvent->event_timestamp)),
            'eventsReceived' => $events->count(),
            'state' => $lastEvent->event_type === 'end' ? 'ended' : 'active',
            'events' => $events
        ]);
    }
}