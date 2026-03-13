<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\WatchSession;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function ingest(Request $request)
    {
        // Quick validation on the request and ensuring we are giving a reasonable response on failure
        try {
            $data = $request->validate([
                'sessionId' => 'required|string',
                'userId' => 'required|string',
                'eventType' => 'required|string|in:start,heartbeat,pause,resume,seek,quality_change,buffer_start,buffer_end,end',
                'eventId' => 'required|string',
                'eventTimestamp' => 'nullable|date',
                'receivedAt' => 'nullable|date',
                'payload' => 'required|array', // JSON object from client
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'error' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'An unexpected error has occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
        
        $eventType = $data['eventType'];
        $now = now();
        $decodedPayload = is_string($data['payload']) 
            ? json_decode($data['payload'], true) 
            : $data['payload'];

        // Upsert the Event using eventId
        $event = Event::updateOrCreate(
            ['eventId' => $data['eventId']],
            [
                'sessionId' => $data['sessionId'],
                'userId' => $data['userId'],
                'eventType' => $data['eventType'],
                'eventTimestamp' => $data['eventTimestamp'] ?? $now,
                'receivedAt' => $data['receivedAt'] ?? $now,
                'payload' => $decodedPayload,
            ]
        );

        // Upsert the WatchSession using sessionId
        $watchSession = WatchSession::updateOrCreate(
            ['sessionId' => $data['sessionId']],
            [
                'userId' => $data['userId'],
                'eventId' => $data['eventId'],  // Should this be using the eventId from the payload? Should those be the same in the example data?
                'started_at' => $now,
                'current_position' => $data['payload']['position'] ?? null,
                'current_quality' => $data['payload']['quality'] ?? null,
            ]
        );

        // Update WatchSession state based on event type
        // @todo Move this to a service.. refactor and decouple
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
                $watchSession->current_position = $data['payload']['position'] ?? $watchSession->current_position;
                $watchSession->last_seen_at = $now;
                break;
            case 'quality_change':
                $watchSession->current_quality = $data['payload']['quality'] ?? $watchSession->current_quality;
                $watchSession->last_seen_at = $now;
                break;
            case 'buffer_start':
                $watchSession->status = 'paused'; // or 'buffering' if you add a state later
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

        // Ensure the watch session is saved after the above shenanigans and return output
        $watchSession->save();

        return response()->json([
            'message' => 'Event ingested successfully',
            'eventId' => $event->eventId,
            'sessionId' => $watchSession->sessionId,
            'status' => $watchSession->status,
        ], 201);
    }
}