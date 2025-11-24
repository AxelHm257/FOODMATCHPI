<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de ventas</title>
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
                <a href="{{ route('provider.dashboard') }}" class="inline-flex items-center">
                    <img src="{{ $logoUrl }}" alt="Logo" class="fm-nav-logo" />
                </a>
                @endif
                <a href="{{ route('provider.dashboard') }}" class="px-3 py-2 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Mis productos</a>
                <a href="{{ route('provider.reports') }}" class="px-3 py-2 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Reportes</a>
            </div>
            <div>
                <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="px-3 py-2 border border-red-600 text-red-700 rounded hover:bg-red-50">Cerrar sesión</a>
                <form id="logout-form" action="/logout" method="POST" class="hidden">@csrf</form>
            </div>
        </nav>
        <h1 class="text-3xl font-bold mb-6">Reportes de ventas</h1>

        <form method="GET" action="{{ route('provider.reports') }}" class="mb-6 bg-white rounded-lg shadow p-4 flex flex-wrap gap-3 items-end">
            <div>
                <label for="from" class="block text-sm font-medium text-gray-700">Desde</label>
                <input id="from" name="from" type="date" value="{{ $from }}" class="mt-1 border rounded px-3 py-2">
            </div>
            <div>
                <label for="to" class="block text-sm font-medium text-gray-700">Hasta</label>
                <input id="to" name="to" type="date" value="{{ substr($to, 0, 10) }}" class="mt-1 border rounded px-3 py-2">
            </div>
            <button class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Filtrar</button>
        </form>

        @if (($totals['orders_count'] ?? 0) > 0)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-gray-500 text-sm">Pedidos</div>
                    <div class="text-2xl font-bold">{{ $totals['orders_count'] }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-gray-500 text-sm">Subtotal vendido</div>
                    <div class="text-2xl font-bold">${{ number_format($totals['subtotal'], 2) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-gray-500 text-sm">Ingreso estimado (pagado)</div>
                    <div class="text-2xl font-bold">${{ number_format(max(0, $totals['estimated_income_paid']), 2) }}</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <h2 class="text-xl font-semibold mb-3">Estados</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="border rounded p-3"><div class="text-sm text-gray-500">Pendiente</div><div class="text-lg font-bold">{{ $statusCounts['pending'] }}</div></div>
                    <div class="border rounded p-3"><div class="text-sm text-gray-500">Pagado</div><div class="text-lg font-bold">{{ $statusCounts['paid'] }}</div></div>
                    <div class="border rounded p-3"><div class="text-sm text-gray-500">Cancelado</div><div class="text-lg font-bold">{{ $statusCounts['cancelled'] }}</div></div>
                    <div class="border rounded p-3"><div class="text-sm text-gray-500">Reembolso solicitado</div><div class="text-lg font-bold">{{ $statusCounts['refund_requested'] }}</div></div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ventas</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($productSummary as $row)
                            <tr>
                                @php($exists = optional(auth()->user()->provider)->products?->contains('name', $row['name']))
                                <td class="px-4 py-2">{{ $exists ? $row['name'] : 'Producto eliminado' }}</td>
                                <td class="px-4 py-2">{{ $row['qty'] }}</td>
                                <td class="px-4 py-2">${{ number_format($row['sales'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="rounded-md bg-yellow-50 border border-yellow-200 p-3 text-yellow-800">Aún no registras ventas.</div>
        @endif
    </div>
</body>
</html>
