<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StampCorrectionRequest extends Model
{
    protected $fillable = [
        'attendance_id',
        'requested_clock_in_at',
        'requested_clock_out_at',
        'requested_break_minutes',
        'requested_note',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
    ];
    protected $casts = [
        'requested_clock_in_at'  => 'datetime',
        'requested_clock_out_at' => 'datetime',
        'approved_at'            => 'datetime',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}