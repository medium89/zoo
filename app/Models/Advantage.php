<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advantage extends Model
{
    protected $fillable = ['image', 'title', 'text', 'active', 'order'];
    use HasFactory;

    protected $casts = [
        'active' => 'boolean',
    ];
}
