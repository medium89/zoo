<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Social extends Model
{
    protected $fillable = ['icon', 'title', 'link', 'link_text', 'text', 'order', 'active'];
    use HasFactory;

    protected $casts = [
        'active' => 'boolean',
    ];
}
