<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'name',
        'description',
        'category',
        'price',
        'image_url',
        'is_available',
    ];

    // Relación: Un platillo pertenece a un proveedor
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
