<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationApplication extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'status',
        'message',
        'interview_mode',
        'interview_at',
        'interview_place',
        'interview_notes',
        'feedback',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'interview_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organization_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'interview_scheduled'], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Awaiting interview',
            'interview_scheduled' => 'Interview scheduled',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }
}
