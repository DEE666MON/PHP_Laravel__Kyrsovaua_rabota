<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationAction extends Model
{
    protected $fillable = [
        'actor_id', 'action', 'target_user_id', 'target_message_id',
        'payload', 'undone_at', 'undone_by',
    ];

    protected $casts = [
        'payload'   => 'array',
        'undone_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function undoneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'undone_by');
    }
}