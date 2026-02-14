<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_closed',
        'title',
        'description',
        'robots',
        'charset',
        'og_title',
        'og_description',
        'og_image',
        'og_url',
        'personal_data_consent_text',
    ];
}
