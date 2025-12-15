<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvitoReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'review_date',
        'text',
        'photos',
        'status',
        'source_hash',
    ];

    protected $casts = [
        'review_date' => 'datetime',
        'photos' => 'array',
    ];
}

