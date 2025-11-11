<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceBreak extends Model
{
    protected $fillable = ['attendance_id', 'break_in_at', 'break_out_at'];
    protected $casts = [
        'break_in_at'  => 'datetime',
        'break_out_at' => 'datetime',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}