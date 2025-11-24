<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceRequest extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'tax_id',
        'business_name',
        'email',
        'status',
    ];
}

