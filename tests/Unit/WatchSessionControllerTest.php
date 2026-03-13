<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\WatchSession;

class WatchSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_active_count_for_event()
    {
        // Seed some sessions using factory
        WatchSession::factory()->create([
            'sessionId' => 'abc-123',
            'userId' => 'user-001',
            'eventId' => 'evt-100',
            'status' => 'active',
        ]);

        WatchSession::factory()->create([
            'sessionId' => 'abc-124',
            'userId' => 'user-002',
            'eventId' => 'evt-100',
            'status' => 'paused',
        ]);

        WatchSession::factory()->create([
            'sessionId' => 'abc-125',
            'userId' => 'user-003',
            'eventId' => 'evt-100',
            'status' => 'active',
        ]);

        $response = $this->getJson('/v1/watch-sessions/active-count/evt-100');

        $response->assertStatus(200)
                 ->assertJson([
                     'eventId' => 'evt-100',
                     'activeSessions' => 2,
                 ]);
    }
}