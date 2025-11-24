<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderRating extends Model
{
    protected $fillable = [
        'provider_id',
        'user_id',
        'stars',
        'comment',
    ];
}

