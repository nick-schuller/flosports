<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Event;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition()
    {
        return [
            'sessionId' => $this->faker->uuid,
            'userId' => 'user-' . $this->faker->numberBetween(1, 1000),
            'eventType' => $this->faker->randomElement([
                'start', 'heartbeat', 'pause', 'resume',
                'seek', 'quality_change', 'buffer_start', 'buffer_end', 'end'
            ]),
            'eventId' => 'evt-' . $this->faker->numberBetween(1, 10000),
            'eventTimestamp' => now(),
            'receivedAt' => now(),
            'payload' => [
                'eventId' => 'evt-' . $this->faker->numberBetween(1, 10000),
                'position' => $this->faker->randomFloat(2, 0, 3600),
                'quality' => $this->faker->randomElement(['720p', '1080p'])
            ],
        ];
    }
}