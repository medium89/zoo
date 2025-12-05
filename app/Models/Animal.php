<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function boardings()
    {
        return $this->hasMany(Boarding::class, 'name', 'name');
    }
}
