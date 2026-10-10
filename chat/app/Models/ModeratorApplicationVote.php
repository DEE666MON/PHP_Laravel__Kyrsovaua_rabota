<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModeratorApplicationVote extends Model
{
    protected $fillable = ['application_id', 'voter_id', 'approve'];
    protected $casts = ['approve' => 'boolean'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(ModeratorApplication::class, 'application_id');
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voter_id');
    }
}