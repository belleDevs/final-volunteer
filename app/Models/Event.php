<?php

namespace App\Models;

use App\Support\ScheduleClock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'location',
        'status',
        'start_time',
        'end_time',
        'capacity',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(User::class, 'organization_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function photos()
    {
        return $this->hasMany(EventPhoto::class)->orderBy('id');
    }

    /**
     * Schedule state for a posted event. Draft remains only for older records.
     */
    public function oversightStatus(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if ($this->status === 'draft') {
            return 'review';
        }

        if ($this->status === 'completed') {
            return 'completed';
        }

        $phase = ScheduleClock::phase($this->start_time, $this->end_time, $this->status);

        return match ($phase) {
            'upcoming' => 'upcoming',
            'past' => 'ended',
            default => 'ongoing',
        };
    }

    public function oversightLabel(): string
    {
        return match ($this->oversightStatus()) {
            'upcoming' => 'Upcoming',
            'ongoing' => 'Ongoing',
            'ended' => 'Ended',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => 'Needs review',
        };
    }
}
