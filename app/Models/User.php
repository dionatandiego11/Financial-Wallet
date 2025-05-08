<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; 

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'balance', 
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'balance' => 'decimal:2', 
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Auxiliar para obter o usuário pelo e-mail (para transferências)
    public static function findByEmail(string $email)
    {
        return static::where('email', $email)->first();
    }
}