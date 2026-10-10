<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Модератор ИЛИ админ — используется для проверок прав.
    public function isModerator(): bool
    {
        return in_array($this->role, ['moderator', 'admin'], true);
    }

    // Именно модератор (без админа) — для подсчёта голосов в заявках.
    public function isPureModerator(): bool
    {
        return $this->role === 'moderator';
    }

    public function chats()
    {
        return $this->hasMany(Chat::class, 'creator_id');
    }

    public function applications()
    {
        return $this->hasMany(ModeratorApplication::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
