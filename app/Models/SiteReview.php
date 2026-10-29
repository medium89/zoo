<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteReview extends Model
{
    protected $fillable = ['name', 'text', 'rating', 'status'];

    protected $casts = ['rating' => 'integer'];
}
