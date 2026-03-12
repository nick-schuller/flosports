<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Carbon\Carbon;

class EventController extends Controller
{
    /**
     * Ingest an event.
     * POST /v1/events
     */
    public function ingest(Request $request)
    {
        try {
            $data = $request->validate([
                'sessionId' => 'required',   // This needs to change to int eventually with next version and be |integer
                'userId' => 'required',      // This needs to change to int eventually with next version and be |integer
                'eventType' => 'required|string|in:start,heartbeat,pause,resume,seek,quality_change,buffer_start,buffer_end,end',   // These really should be individual events or an enum or something..
                'eventId' => 'required',     // This should also correlate to some actual event table and be |integer
                'eventTimestamp' => 'nullable|date',
                'receivedAt' => 'nullable|date',
                'payload' => 'required|string', // This should be a JSON string
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors as JSON with 422 status
            return response()->json([
                'message' => 'Validation failed',
                'error' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'An unexpected error has occurred',
                'error' => $e->getMessage(),    // This probably shouldn't just be dumped here for an actual prod app
            ], 500);
        }

        // Decode the JSON payload
        $decodedPayload = json_decode($data['payload'], true);

        if ($decodedPayload === null && json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'message' => 'Invalid JSON in payload',
                'error' => json_last_error_msg(),
            ], 422);
        }

        $eventType = $data['eventType'];

        // Do something with the actual eventType instead of just creating a new entry for it
        switch ($eventType) {
            case 'start':
                break;
            case 'heartbeat':
                break;
            case 'pause':
                break;
            case 'resume':
                break;
            case 'seek':
                break;
            case 'quality_change':
                break;
            case 'buffer_start':
                break;
            case 'buffer_end':
                break;
            case 'end':
                break;
        }

        // Save event
        $event = Event::firstOrCreate([
            'sessionId' => $data['sessionId'],
            'userId' => $data['userId'],
            'eventType' => $data['eventType'],
            'eventId' => $data['eventId'],
            'eventTimestamp' => $data['eventTimestamp'] ?? now(),
            'receivedAt' => $data['receivedAt'] ?? now(),
            'payload' => json_encode($data['payload']),
        ]);

        return response()->json([
            'message' => 'Event ingested successfully',
            'event' => $event,
        ], 201);
    }

    /**
     * Return active session count for a given eventId
     * GET /v1/events/{eventId}/active-sessions
     */
    public function activeSessions($eventId)
    {
        // A session is considered "active" if it has a heartbeat or other event in the last 30 seconds
        $threshold = Carbon::now()->subSeconds(30);

        $activeSessions = Event::whereJsonContains('payload->eventId', $eventId)
            ->where('eventTimestamp', '>=', $threshold)
            ->distinct('sessionId')
            ->count('sessionId');

        // @todo In the future we may want to change this to have a different response return if the active session doesn't actually exist
        return response()->json([
            'eventId' => $eventId,
            'activeSessions' => $activeSessions,
        ]);
    }

    /**
     * Return session details for a given sessionId
     * GET /v1/sessions/{sessionId}
     */
    public function sessionDetails($sessionId)
    {
        $events = Event::where('sessionId', $sessionId)
            ->orderBy('eventTimestamp')
            ->get();

        if ($events->isEmpty()) {
            return response()->json([
                'message' => 'Session not found',
            ], 404);
        }

        $firstEvent = $events->first();
        $lastEvent = $events->last();
        $duration = Carbon::parse($firstEvent->eventTimestamp)
            ->diffInSeconds(Carbon::parse($lastEvent->eventTimestamp));

        return response()->json([
            'sessionId' => $sessionId,
            'durationSeconds' => $duration,
            'events' => $events,
        ]);
    }
}