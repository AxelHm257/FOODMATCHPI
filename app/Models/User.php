<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    // Solo usamos HasFactory y Notifiable. HasApiTokens ha sido ELIMINADO.
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // Permite la asignación masiva del rol
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => 'string', // Trata el campo ENUM como string
    ];

    // RELACIÓN: Define la relación uno-a-uno con el modelo Provider
    public function provider(): HasOne
    {
        return $this->hasOne(Provider::class);
    }
}
