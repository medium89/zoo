<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderService extends Model
{
    use HasFactory;

    protected $fillable = ['service_order_id', 'service_order_animal_id', 'service_type', 'units_per_day', 'unit_price'];

    protected $casts = ['units_per_day' => 'integer', 'unit_price' => 'integer'];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function orderAnimal()
    {
        return $this->belongsTo(ServiceOrderAnimal::class, 'service_order_animal_id');
    }
}
