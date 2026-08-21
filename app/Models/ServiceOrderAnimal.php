<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderAnimal extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_order_id', 'animal_id', 'category_id', 'label', 'quantity', 'note',
    ];

    protected $casts = ['quantity' => 'integer'];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
