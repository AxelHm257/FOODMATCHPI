<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito</title>
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
                    <a href="{{ route('customer.home') }}" class="inline-flex items-center">
                        <img src="{{ $logoUrl }}" alt="Logo" class="fm-nav-logo" />
                    </a>
                @endif
                <a href="{{ route('customer.home') }}" class="px-3 py-2 border rounded {{ request()->routeIs('customer.home') ? 'bg-indigo-600 text-white border-indigo-600' : 'border-indigo-600 text-indigo-700 hover:bg-indigo-50' }}">Inicio</a>
                <a href="{{ route('orders.index') }}" class="px-3 py-2 border rounded {{ request()->routeIs('orders.index') ? 'bg-blue-600 text-white border-blue-600' : 'border-blue-600 text-blue-700 hover:bg-blue-50' }}">Mis pedidos</a>
            </div>
            <div>
                <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="px-3 py-2 border border-red-600 text-red-700 rounded hover:bg-red-50">Cerrar sesión</a>
                <form id="logout-form" action="/logout" method="POST" class="hidden">@csrf</form>
            </div>
        </nav>
        <h1 class="text-3xl font-bold mb-4">Tu carrito</h1>

        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-3 text-green-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-red-700">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Precio</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($cart as $key => $item)
                        <tr>
                            <td class="px-4 py-2">{{ $item['name'] }}</td>
                            <td class="px-4 py-2">${{ number_format($item['price'], 2) }}</td>
                            <td class="px-4 py-2">{{ $item['qty'] }}</td>
                            <td class="px-4 py-2">
                                <div class="flex items-center gap-2">
                                    @if (!is_null($item['product_id']))
                                        <form method="POST" action="{{ route('cart.add', $item['product_id']) }}" class="inline">
                                            @csrf
                                            <button class="px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700">+</button>
                                        </form>
                                        <form method="POST" action="{{ route('cart.remove', $item['product_id']) }}" class="inline">
                                            @csrf
                                            <button class="px-3 py-1 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">-</button>
                                        </form>
                                        <form method="POST" action="{{ route('cart.delete', $item['product_id']) }}" class="inline" onsubmit="return confirm('¿Eliminar este producto del carrito?');">
                                            @csrf
                                            <button class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">Eliminar</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('cart.deleteMix', $key) }}" class="inline" onsubmit="return confirm('¿Eliminar este combo del carrito?');">
                                            @csrf
                                            <button class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if (!empty($item['extras']) || !empty($item['note']) || ($item['type'] ?? null) === 'mix')
                            <tr>
                                <td></td>
                                <td colspan="4" class="px-4 pb-3 text-sm text-gray-600">
                                    @if (($item['type'] ?? null) === 'mix' && !empty($item['components']))
                                        <div class="mb-2">
                                            <span class="font-medium">Componentes:</span>
                                            <ul class="list-disc pl-5">
                                                @foreach ($item['components'] as $c)
                                                    <li>{{ $c['name'] }} × {{ $c['qty'] }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    @if (!empty($item['extras']))
                                        <div>Extras: {{ implode(', ', $item['extras']) }}</div>
                                    @endif
                                    @if (!empty($item['note']))
                                        <div>Nota: {{ $item['note'] }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">Tu carrito está vacío</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
        <div class="mt-4 flex items-center justify-between bg-white rounded-lg shadow p-4">
            <div class="text-gray-700">
                Subtotal: <span class="font-semibold">${{ number_format($subtotal, 2) }}</span>
                · Comisión ({{ number_format($commissionRate*100, 0) }}%): <span class="font-semibold">${{ number_format($commission, 2) }}</span>
                · Total: <span class="font-bold">${{ number_format($total, 2) }}</span>
            </div>
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('cart.clear') }}">
                    @csrf
                    <button class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Vaciar</button>
                </form>
                <a href="{{ route('cart.checkout') }}" class="inline-flex items-center px-4 py-2 border border-green-600 text-green-700 rounded hover:bg-green-50">Proceder a pagar</a>
                <form method="POST" action="{{ route('cart.place') }}" class="inline">
                    @csrf
                    <button class="inline-flex items-center px-4 py-2 border border-blue-600 text-blue-700 rounded hover:bg-blue-50">Realizar pedido</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
