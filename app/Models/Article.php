<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'excerpt',
        'content',
        'published_at',
        'active',
        'slug',
        'seo_title',
        'seo_description',
        'seo_robots',
        'seo_charset',
        'cover_path',
        'order',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function images()
    {
        return $this->hasMany(ArticleImage::class)->orderBy('order');
    }

    public function comments()
    {
        return $this->hasMany(ArticleComment::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
