<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['title','excerpt','content','published_at','active'];

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
}
