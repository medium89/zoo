<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'species',
        'description',
        'note',
        'order',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function boardings()
    {
        return $this->hasMany(Boarding::class);
    }

    public function legacyBoardings()
    {
        return $this->hasMany(Boarding::class, 'name', 'name');
    }

    public function photos()
    {
        return $this->hasMany(AnimalPhoto::class);
    }
}
