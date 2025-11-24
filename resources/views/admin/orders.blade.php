<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos</title>
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
                <a href="{{ route('admin.users') }}" class="px-3 py-2 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Usuarios</a>
            </div>
            <div>
                <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="px-3 py-2 border border-red-600 text-red-700 rounded hover:bg-red-50">Cerrar sesión</a>
                <form id="logout-form" action="/logout" method="POST" class="hidden">@csrf</form>
            </div>
        </nav>

        <h1 class="text-3xl font-bold mb-6">Gestión de pedidos</h1>

        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-3 text-green-700">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($orders as $o)
                        <tr>
                            <td class="px-4 py-2">{{ $o->id }}</td>
                            <td class="px-4 py-2">{{ optional($o->user)->name ?? 'Usuario #'.$o->user_id }}</td>
                            <td class="px-4 py-2">{{ optional($o->provider)->name ?? 'Proveedor #'.$o->provider_id }}</td>
                            <td class="px-4 py-2">
                                @php
                                    $s = $o->status;
                                    $label = $s === 'pending' ? 'Pendiente' : ($s === 'paid' ? 'Pagado' : ($s === 'cancelled' ? 'Cancelado' : ($s === 'refund_requested' ? 'Reembolso solicitado' : ($s === 'refunded' ? 'Reembolsado' : ($s === 'paused' ? 'Pausado' : ucfirst($s))))));
                                @endphp
                                <span class="inline-block px-2 py-1 border rounded">{{ $label }}</span>
                            </td>
                            <td class="px-4 py-2">${{ number_format($o->total, 2) }}</td>
                            <td class="px-4 py-2">
                                <form method="POST" action="{{ route('admin.orders.status', $o) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="status" class="border rounded px-2 py-1">
                                        <option value="pending" {{ $o->status==='pending' ? 'selected' : '' }}>Pendiente</option>
                                        <option value="paid" {{ $o->status==='paid' ? 'selected' : '' }}>Pagado</option>
                                        <option value="cancelled" {{ $o->status==='cancelled' ? 'selected' : '' }}>Cancelado</option>
                                        <option value="refund_requested" {{ $o->status==='refund_requested' ? 'selected' : '' }}>Reembolso solicitado</option>
                                        <option value="refunded" {{ $o->status==='refunded' ? 'selected' : '' }}>Reembolsado</option>
                                        <option value="paused" {{ $o->status==='paused' ? 'selected' : '' }}>Pausado</option>
                                    </select>
                                    <button class="px-3 py-1 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Actualizar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
