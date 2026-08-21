<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'service_type', 'units_per_day', 'daily_price', 'start_date', 'end_date',
        'address', 'note', 'source', 'status', 'confirmed_at', 'archived_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'confirmed_at' => 'datetime',
        'archived_at' => 'datetime',
        'units_per_day' => 'integer',
        'daily_price' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function animals()
    {
        return $this->hasMany(ServiceOrderAnimal::class);
    }
}
