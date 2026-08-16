<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boarding extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'animal_id',
        'name',
        'description',
        'service_type',
        'units_per_day',
        'unit_price',
        'source',
        'status',
        'start_date',
        'end_date',
        'note',
        'confirmed_at',
        'archived_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'confirmed_at' => 'datetime',
        'archived_at' => 'datetime',
        'units_per_day' => 'integer',
        'unit_price' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function tasks()
    {
        return $this->hasMany(BoardingTask::class);
    }
}
