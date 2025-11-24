<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    protected $fillable = [
        'user_id',
        'provider_id',
        'order_reference',
        'reason',
        'status',
        'evidences',
    ];
}

