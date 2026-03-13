<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\WatchSession;

class WatchSessionFactory extends Factory
{
    protected $model = WatchSession::class;

    public function definition()
    {
        return [
            'sessionId' => $this->faker->uuid,
            'userId' => 'user-' . $this->faker->numberBetween(1, 1000),
            'eventId' => 'evt-' . $this->faker->numberBetween(1, 1000),
            'status' => $this->faker->randomElement(['active', 'paused', 'ended']),
            'started_at' => now()->subMinutes(rand(1, 60)),
            'last_seen_at' => now(),
            'current_position' => $this->faker->randomFloat(2, 0, 3600),
            'current_quality' => $this->faker->randomElement(['720p', '1080p']),
        ];
    }
}