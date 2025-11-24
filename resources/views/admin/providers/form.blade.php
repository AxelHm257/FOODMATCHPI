<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto p-6">
        <nav class="mb-4 bg-white rounded-lg shadow p-3 fm-nav flex items-center justify-between">
            <div class="flex items-center gap-3">
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
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center">
                        <img src="{{ $logoUrl }}" alt="Logo" class="fm-nav-logo" />
                    </a>
                @endif
                <a href="{{ route('admin.providers') }}" class="px-3 py-2 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Volver</a>
            </div>
            <div>
                <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="px-3 py-2 border border-red-600 text-red-700 rounded hover:bg-red-50">Cerrar sesión</a>
                <form id="logout-form" action="/logout" method="POST" class="hidden">@csrf</form>
            </div>
        </nav>

        <h1 class="text-2xl font-bold mb-4">{{ $title }}</h1>

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif

                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700">Propietario</label>
                    <select id="user_id" name="user_id" class="mt-1 w-full border rounded px-3 py-2" required>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ ($provider && $provider->user_id===$u->id) ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', optional($provider)->name) }}" class="mt-1 w-full border rounded px-3 py-2" />
                </div>

                <div>
                    <label for="contact" class="block text-sm font-medium text-gray-700">Contacto</label>
                    <input id="contact" name="contact" type="text" maxlength="255" value="{{ old('contact', optional($provider)->contact) }}" class="mt-1 w-full border rounded px-3 py-2" />
                </div>

                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">Ubicación</label>
                    <input id="location" name="location" type="text" required maxlength="255" value="{{ old('location', optional($provider)->location) }}" class="mt-1 w-full border rounded px-3 py-2" />
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Descripción</label>
                    <textarea id="description" name="description" rows="4" class="mt-1 w-full border rounded px-3 py-2">{{ old('description', optional($provider)->description) }}</textarea>
                </div>

                <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700">Logo (opcional)</label>
                    <input id="logo" name="logo" type="file" accept="image/*" class="mt-1 w-full border rounded px-3 py-2" />
                    @if($provider && $provider->logo_url)
                        @php($img = $provider->logo_url)
                        @php($link = (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) ? $img : asset($img))
                        <p class="text-xs text-gray-500 mt-1">Actual: <a href="{{ $link }}" target="_blank" class="underline">ver logo</a></p>
                    @endif
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Guardar</button>
                    <a href="{{ route('admin.providers') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
