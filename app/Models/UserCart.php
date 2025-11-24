<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCart extends Model
{
    protected $table = 'user_carts';
    public $timestamps = false;
    protected $fillable = ['user_id', 'cart'];
}

