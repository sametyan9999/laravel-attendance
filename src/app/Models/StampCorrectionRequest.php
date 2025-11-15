<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StampCorrectionRequest extends Model
{
    protected $fillable = [
        'attendance_id',
        'requested_by',
        'approved_by',
        'status',
        'requested_clock_in_at',
        'requested_clock_out_at',
        'requested_break_minutes',
        'requested_note',
        'approved_at',
    ];

    protected $casts = [
        'requested_clock_in_at'  => 'datetime',
        'requested_clock_out_at' => 'datetime',
        'approved_at'            => 'datetime',
        'requested_break_minutes'=> 'integer',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}