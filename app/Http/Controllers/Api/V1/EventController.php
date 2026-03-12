<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\WatchSession;
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
        $now = now();

        // Upsert watch session for sessionId
        $watchSession = WatchSession::firstOrNew(
            ['sessionId' => (string) $data['sessionId']],
            [
                'userId' => (string) $data['userId'],
                'eventId' => $data['eventId'],
                'started_at' => $now,
            ]
        );

        switch ($eventType) {
            case 'start':
                $watchSession->status = 'active';
                $watchSession->started_at = $now;
                $watchSession->last_seen_at = $now;
                break;
            case 'heartbeat':
                $watchSession->status = 'active';
                $watchSession->last_seen_at = $now;
                break;
            case 'pause':
                $watchSession->status = 'paused';
                $watchSession->last_seen_at = $now;
                break;
            case 'resume':
                $watchSession->status = 'active';
                $watchSession->last_seen_at = $now;
                break;
            case 'seek':
                $watchSession->current_position = $decodedPayload['position'] ?? $watchSession->current_position;
                $watchSession->last_seen_at = $now;
                break;
            case 'quality_change':
                $watchSession->current_quality = $decodedPayload['quality'] ?? $watchSession->current_quality;
                $watchSession->last_seen_at = $now;
                break;
            case 'buffer_start':
                $watchSession->status = 'paused'; // optional 'buffering' state
                $watchSession->last_seen_at = $now;
                break;
            case 'buffer_end':
                $watchSession->status = 'active';
                $watchSession->last_seen_at = $now;
                break;
            case 'end':
                $watchSession->status = 'ended';
                $watchSession->last_seen_at = $now;
                break;
        }

        $watchSession->save();

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
}