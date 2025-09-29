<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetCalendarEntry extends Model
{
    use HasFactory;

    protected $fillable = ['pet_id','date','service','slot'];

    protected $casts = [
        'date' => 'date',
    ];

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }
}

