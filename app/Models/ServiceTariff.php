<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTariff extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type',
        'animal_group',
        'amount',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];
}
