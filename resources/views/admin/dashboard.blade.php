<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
</head>
<body class="bg-gray-50">
    <div class="max-w-5xl mx-auto p-6">
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
                <a href="{{ route('admin.providers') }}" class="px-3 py-2 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Proveedores</a>
                <a href="{{ route('admin.users') }}" class="px-3 py-2 border rounded {{ request()->routeIs('admin.users') ? 'bg-indigo-600 text-white border-indigo-600' : 'border-indigo-600 text-indigo-700 hover:bg-indigo-50' }}">Usuarios</a>
                <a href="{{ route('admin.orders') }}" class="px-3 py-2 border rounded {{ request()->routeIs('admin.orders') ? 'bg-indigo-600 text-white border-indigo-600' : 'border-indigo-600 text-indigo-700 hover:bg-indigo-50' }}">Pedidos</a>
            </div>
            <div>
                <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="px-3 py-2 border border-red-600 text-red-700 rounded hover:bg-red-50">Cerrar sesión</a>
                <form id="logout-form" action="/logout" method="POST" class="hidden">@csrf</form>
            </div>
        </nav>
        <h1 class="text-3xl font-bold mb-6">Panel de Administración</h1>
        <p class="text-gray-600">Bienvenido. Aquí podrás gestionar la aplicación.</p>
    </div>
    </body>
</html>
