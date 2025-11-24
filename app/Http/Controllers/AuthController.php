<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\UserCart;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        if ($request->filled('website')) {
            abort(422);
        }
        $request->merge([
            'name' => is_string($request->name) ? trim($request->name) : $request->name,
            'email' => is_string($request->email) ? strtolower(trim($request->email)) : $request->email,
        ]);
        $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:50',
                "regex:/^[\\p{L}\\s'\\-]+$/u",
                'not_regex:/^\s+$/',
            ],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:64',
                'confirmed',
                'different:name',
                'not_in:password,123456,123456789,qwerty,12345678,111111,123123',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,64}$/',
            ],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre debe tener como máximo 50 caracteres.',
            'name.regex' => 'El nombre solo puede contener letras, espacios, apóstrofes y guiones.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'Debe ser un email válido.',
            'email.unique' => 'Este email ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.max' => 'La contraseña no debe superar 64 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.different' => 'La contraseña no puede ser igual al nombre.',
            'password.not_in' => 'La contraseña es demasiado común.',
            'password.regex' => 'La contraseña debe incluir mayúsculas, minúsculas, números y un carácter especial.',
        ]);

        // 2. Crear el usuario con el rol 'customer'
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'customer', // ROL FIJO: Cliente por defecto
        ]);

        // 3. Iniciar sesión inmediatamente
        Auth::login($user);

        // 4. Redirigir al cliente a su vista de menú
        return redirect()->route('customer.home');
    }

    // Método para manejar el inicio de sesión
    public function login(Request $request)
    {
        if ($request->filled('website')) {
            abort(422);
        }
        $request->merge([
            'email' => is_string($request->email) ? strtolower(trim($request->email)) : $request->email,
        ]);
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:64'],
        ], [
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'Debe ser un email válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.max' => 'La contraseña no debe superar 64 caracteres.',
        ]);

        // 2. Intentar autenticar al usuario
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->role === 'provider' && !$user->provider) {
                Provider::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'contact' => '',
                    'logo_url' => null,
                    'location' => '',
                    'description' => '',
                ]);
                $user->refresh();
            }

            // 3. Redirección basada en el rol
            $redirectPath = match ($user->role) {
                'admin'    => route('admin.dashboard'),
                'provider' => route('provider.dashboard'),
                'customer' => route('customer.home'),
                default    => '/',
            };
            // Restaurar carrito desde BD si existe
            if ($user && ($cartRecord = UserCart::where('user_id', $user->id)->first())) {
                $cartJson = $cartRecord->cart;
                if (!empty($cartJson)) {
                    $cart = json_decode($cartJson, true) ?: [];
                    $request->session()->put('cart', $cart);
                }
            }
            return redirect()->intended($redirectPath);
        }

        // 4. Fallo en la autenticación (respuesta amigable y con persistencia de input)
        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    // Método de logout
    public function logout(Request $request)
    {
        $userId = Auth::id();
        $cart = $request->session()->get('cart', []);
        if ($userId) {
            \App\Models\UserCart::updateOrCreate(
                ['user_id' => $userId],
                ['cart' => json_encode($cart, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
            );
        }

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
