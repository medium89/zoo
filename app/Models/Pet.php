<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'owner_name','owner_phone','animal_type','services', 'service_type', 'description', 'pluses', 'minuses', 'active',
    ];

    protected $casts = [
        'pluses' => 'array',
        'minuses' => 'array',
        'active' => 'boolean',
        'services' => 'array',
    ];

    public function photos()
    {
        return $this->hasMany(PetPhoto::class)->orderBy('order');
    }
}
