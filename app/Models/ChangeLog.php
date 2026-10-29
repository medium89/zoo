<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeLog extends Model
{
    protected $fillable = ['user_id', 'route', 'method', 'summary', 'changes', 'media_backups', 'reverted_at', 'reverted_by'];

    protected $casts = [
        'changes' => 'encrypted:array',
        'media_backups' => 'encrypted:array',
        'reverted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reverted_by');
    }
}
