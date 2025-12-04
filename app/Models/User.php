<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <-- WAJIB

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // <-- Tambahkan HasApiTokens

    protected $fillable = [
        'name',
        'email',
        'password',
        'photo_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function defaultBook()
    {
        return $this->hasOne(Book::class)->where('is_default', true);
    }
}
