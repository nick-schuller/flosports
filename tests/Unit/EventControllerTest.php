<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Event;
use App\Models\WatchSession;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_ingest_a_start_event()
    {
        $sessionId = 'abc-123';
        $userId = 'user-001';
        $eventId = 'evt-001';

        $payload = [
            "eventId" => $eventId,
            "position" => 0,
            "quality" => "1080p"
        ];

        $response = $this->postJson('/v1/events', [
            'sessionId' => $sessionId,
            'userId' => $userId,
            'eventType' => 'start',
            'eventId' => $eventId,
            'eventTimestamp' => now()->toIso8601String(),
            'receivedAt' => now()->toIso8601String(),
            'payload' => $payload,
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Event ingested successfully',
                 ]);

        $this->assertDatabaseHas('events', ['eventId' => $eventId]);
        $this->assertDatabaseHas('watch_sessions', ['sessionId' => $sessionId, 'status' => 'active']);
    }

    /** @test */
    public function it_returns_validation_error_for_invalid_event_type()
    {
        $event = Event::factory()->make([
            'eventType' => 'invalid_event'
        ]);

        $response = $this->postJson('/v1/events', $event->toArray());

        $response->assertStatus(422)
                 ->assertJsonStructure(['message', 'error']);
    }

    /** @test */
    public function it_updates_existing_event_on_duplicate_eventId()
    {
        $sessionId = 'abc-123';
        $userId = 'user-001';
        $eventId = 'evt-001';

        $payload = [
            "eventId" => $eventId,
            "position" => 0,
            "quality" => "1080p"
        ];

        $response = $this->postJson('/v1/events', [
            'sessionId' => $sessionId,
            'userId' => $userId,
            'eventType' => 'start',
            'eventId' => $eventId,
            'eventTimestamp' => now()->toIso8601String(),
            'receivedAt' => now()->toIso8601String(),
            'payload' => $payload,
        ]);

        // Change the payload a bit but use the same eventId
        $newPayload = ['eventId' => $eventId, 'position' => 20, 'quality' => '1080p'];

        // This should update the existing event
        $response = $this->postJson('/v1/events', [
            'sessionId' => $sessionId,
            'userId' => $userId,
            'eventType' => 'start',
            'eventId' => $eventId,
            'eventTimestamp' => now()->toIso8601String(),
            'receivedAt' => now()->toIso8601String(),
            'payload' => $newPayload,
        ]);

        $this->assertDatabaseHas('events', [
            'eventId' => $eventId,
            'payload' => json_encode($newPayload),
        ]);
        
        // And ensure here we still only have 1 event as it should've been updated
        $this->assertDatabaseCount('events', 1);
    }
}