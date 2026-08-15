<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardingTaskMessage extends Model
{
    protected $fillable = [
        'boarding_task_run_id',
        'chat_id',
        'message_id',
    ];

    public function run()
    {
        return $this->belongsTo(BoardingTaskRun::class, 'boarding_task_run_id');
    }
}
