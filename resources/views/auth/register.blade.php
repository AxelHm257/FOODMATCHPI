<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | FoodMatch</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md bg-white rounded-lg shadow-xl p-8 space-y-6">
        <div class="text-center">
            @php
                $files = Storage::disk('public')->files('platform_logo');
                if (empty($files)) {
                    $files = Storage::disk('public')->files('provider_logos');
                    $preferred = array_values(array_filter($files, function($f){ return preg_match('/foodmatch/i', basename($f)); }));
                    if(count($preferred)) { $files = $preferred; }
                }
                $images = array_values(array_filter($files, function($f){ return preg_match('/\.(png|jpg|jpeg|webp)$/i', $f); }));
                $logo = null;
                if(count($images)){
                    usort($images, function($a,$b){ return Storage::disk('public')->lastModified($a) <=> Storage::disk('public')->lastModified($b); });
                    $logo = end($images);
                }
                $logoUrl = $logo ? asset('storage/'.$logo) : null;
            @endphp
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo" class="mx-auto fm-auth-logo mb-2" />
            @endif
            <p class="text-xl text-gray-600 mt-2">Regístrate como Cliente</p>
        </div>

        <form method="POST" action="/register" class="space-y-6" autocomplete="on">
            @csrf <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                <input id="name" type="text" name="name" required autofocus minlength="3" maxlength="50" value="{{ old('name') }}" pattern="^[\p{L}\s'\-]+$" title="Solo letras, espacios, apóstrofes y guiones"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @error('name')
                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" type="email" name="email" required value="{{ old('email') }}" maxlength="255" autocomplete="email" inputmode="email"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @error('email')
                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                <div class="relative">
                    <input id="password" type="password" name="password" required minlength="8" maxlength="64" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,64}" title="Debe incluir mayúsculas, minúsculas, números y un carácter especial"
                           class="mt-1 block w-full px-3 py-2 pr-16 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-600 toggle-password cursor-pointer select-none" data-target="password" aria-pressed="false" aria-label="Mostrar contraseña">Mostrar</button>
                </div>
                @error('password')
                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar Contraseña</label>
                <div class="relative">
                    <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" maxlength="64" autocomplete="new-password"
                           class="mt-1 block w-full px-3 py-2 pr-16 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-600 toggle-password cursor-pointer select-none" data-target="password_confirmation" aria-pressed="false" aria-label="Mostrar contraseña">Mostrar</button>
                </div>
            </div>
            <input type="text" name="website" tabindex="-1" aria-hidden="true" class="hidden" />

            <div>
                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600">
                    Registrar
                </button>
            </div>
        </form>

        <p class="text-center text-sm text-gray-600">
            ¿Ya tienes cuenta?
            <a href="/login" class="font-medium text-indigo-600 hover:text-indigo-500">
                Inicia sesión aquí
            </a>
        </p>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.toggle-password').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-target');
            var input = document.getElementById(id);
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Ocultar' : 'Mostrar';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
          });
        });
      });
    </script>
</body>
</html>
