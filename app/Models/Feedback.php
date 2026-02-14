<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'message',
        'status',
        'order',
        'personal_data_consent',
        'personal_data_consent_at',
        'personal_data_consent_text',
        'personal_data_consent_hash',
    ];

    protected $casts = [
        'personal_data_consent' => 'boolean',
        'personal_data_consent_at' => 'datetime',
    ];
}
