<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModeratorApplication extends Model
{
    protected $fillable = ['user_id', 'reason', 'status', 'decided_by', 'decided_at'];

    protected $casts = ['decided_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ModeratorApplicationVote::class, 'application_id');
    }

    // Голоса «за» от чистых модераторов (не админов).
    public function approveCount(): int
    {
        return $this->votes()
            ->where('approve', true)
            ->whereHas('voter', fn ($q) => $q->where('role', 'moderator'))
            ->count();
    }

    // Голоса «против» от чистых модераторов.
    public function rejectCount(): int
    {
        return $this->votes()
            ->where('approve', false)
            ->whereHas('voter', fn ($q) => $q->where('role', 'moderator'))
            ->count();
    }
}