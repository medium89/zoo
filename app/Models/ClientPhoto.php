<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'path',
        'telegram_file_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
