<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardingTask extends Model
{
    protected $fillable = [
        'boarding_id',
        'title',
        'instructions',
        'scheduled_time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function boarding()
    {
        return $this->belongsTo(Boarding::class);
    }

    public function runs()
    {
        return $this->hasMany(BoardingTaskRun::class);
    }
}
