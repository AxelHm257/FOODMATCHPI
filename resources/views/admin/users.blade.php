<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios</title>
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
</head>
<body class="bg-gray-50">
    <div class="max-w-6xl mx-auto p-6">
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
                <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Inicio</a>
                <a href="{{ route('admin.providers') }}" class="px-3 py-2 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Proveedores</a>
                <a href="{{ route('admin.orders') }}" class="px-3 py-2 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Pedidos</a>
            </div>
            <div>
                <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="px-3 py-2 border border-red-600 text-red-700 rounded hover:bg-red-50">Cerrar sesión</a>
                <form id="logout-form" action="/logout" method="POST" class="hidden">@csrf</form>
            </div>
        </nav>

        <h1 class="text-3xl font-bold mb-6">Gestión de usuarios</h1>

        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-3 text-green-700">{{ session('success') }}</div>
        @endif

        <div class="mb-4 bg-white rounded-lg shadow p-4">
            <form method="GET" action="{{ route('admin.users') }}" class="flex items-end gap-3">
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Filtrar por rol</label>
                    <select id="role" name="role" class="mt-1 border rounded px-3 py-2">
                        <option value="" {{ empty($role) ? 'selected' : '' }}>Todos</option>
                        <option value="customer" {{ ($role ?? '')==='customer' ? 'selected' : '' }}>Cliente</option>
                        <option value="provider" {{ ($role ?? '')==='provider' ? 'selected' : '' }}>Proveedor</option>
                        <option value="admin" {{ ($role ?? '')==='admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Aplicar</button>
                @if(!empty($role))
                    <a href="{{ route('admin.users') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Correo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $u)
                        <tr>
                            <td class="px-4 py-2">{{ $u->id }}</td>
                            <td class="px-4 py-2">{{ $u->name }}</td>
                            <td class="px-4 py-2">{{ $u->email }}</td>
                            <td class="px-4 py-2">
                                @php($roleLabel = $u->role==='customer' ? 'Cliente' : ($u->role==='provider' ? 'Proveedor' : 'Admin'))
                                <span class="inline-block px-2 py-1 border rounded">{{ $roleLabel }}</span>
                            </td>
                            <td class="px-4 py-2">
                                <form method="POST" action="{{ route('admin.users.role', $u) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="role" class="border rounded px-2 py-1">
                                        <option value="customer" {{ $u->role==='customer' ? 'selected' : '' }}>Cliente</option>
                                        <option value="provider" {{ $u->role==='provider' ? 'selected' : '' }}>Proveedor</option>
                                        <option value="admin" {{ $u->role==='admin' ? 'selected' : '' }}>Admin</option>
                                    </select>
                                    <button class="px-3 py-1 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Actualizar</button>
                                </form>
                                <a href="{{ route('admin.users.edit', $u) }}" class="ml-2 px-3 py-1 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50 inline-block">Editar</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
