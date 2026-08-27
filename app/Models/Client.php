<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'map_x',
        'map_y',
        'note',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function animals()
    {
        return $this->hasMany(Animal::class);
    }

    public function photos()
    {
        return $this->hasMany(ClientPhoto::class);
    }

    public function avatarUrl(): string
    {
        $photo = $this->photos->first();

        return $photo?->path
            ? Storage::url($photo->path)
            : asset('images/client-placeholder.svg');
    }

    public function boardings()
    {
        return $this->hasMany(Boarding::class);
    }

    public function serviceOrders()
    {
        return $this->hasMany(ServiceOrder::class);
    }
}
