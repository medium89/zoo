<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageOrder extends Model
{
    protected $fillable = ['section', 'item_key', 'position'];
}
