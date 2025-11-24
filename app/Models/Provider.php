<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Product;
use App\Models\ProviderRating;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'contact',
        'logo_url',
        'location',
        'lat',
        'lng',
        'description',
    ];

    // Relación: Un proveedor pertenece a un usuario
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relación: Un proveedor tiene muchos platillos
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(ProviderRating::class);
    }
}
