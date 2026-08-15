<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardingTaskRun extends Model
{
    protected $fillable = [
        'boarding_task_id',
        'notification_date',
        'status',
        'responded_at',
        'responded_by',
    ];

    protected $casts = [
        'notification_date' => 'date',
        'responded_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(BoardingTask::class, 'boarding_task_id');
    }

    public function messages()
    {
        return $this->hasMany(BoardingTaskMessage::class);
    }
}
