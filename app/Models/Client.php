<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Services\ClientGenderResolver;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'gender',
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

    protected static function booted(): void
    {
        static::saving(function (Client $client) {
            if (!$client->gender || ($client->isDirty('name') && !$client->isDirty('gender'))) {
                $client->gender = app(ClientGenderResolver::class)->resolve($client->name);
            }
        });
    }

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

        if ($photo?->path) {
            return Storage::url($photo->path);
        }

        return match ($this->gender) {
            'female' => asset('images/client-placeholder-female.webp'),
            'male' => asset('images/client-placeholder-male.webp'),
            default => asset('images/client-placeholder.svg'),
        };
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
