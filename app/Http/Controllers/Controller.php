<?php

namespace App\Http\Controllers;

// Estos Traits son comunes en la clase base de Laravel para añadir funcionalidad.
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController; // Laravel usa un alias aquí

// Si estás usando Laravel 9+, la estructura típica es esta:
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
