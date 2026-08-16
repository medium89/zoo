<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'category_id',
        'name',
        'species',
        'dog_size',
        'description',
        'note',
        'tags',
        'order',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function boardings()
    {
        return $this->hasMany(Boarding::class);
    }

    public function legacyBoardings()
    {
        return $this->hasMany(Boarding::class, 'name', 'name');
    }

    public function photos()
    {
        return $this->hasMany(AnimalPhoto::class);
    }

}
