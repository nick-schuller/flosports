<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WatchSession extends Model
{
    protected $table = 'watch_sessions';

    protected $fillable = [
        'sessionId',
        'userId',
        'eventId',
        'status',
        'started_at',
        'last_seen_at',
        'current_position',
        'current_quality',
    ];

    public function events(): HasMany {
        return $this->hasMany(Event::class, 'eventId', 'eventId');
    }
}