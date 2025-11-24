<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Proveedor</title>
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
        <header class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <?php $logo = optional(auth()->user()->provider)->logo_url; ?>
                <?php if (!empty($logo)): ?>
                    <img src="<?= $logo ?>" alt="Logo" class="w-12 h-12 rounded-full object-cover border" />
                <?php else: ?>
                    <div class="w-12 h-12 rounded-full bg-gray-200 border flex items-center justify-center text-gray-500">🟦</div>
                <?php endif; ?>
                <h1 class="text-3xl font-bold">Tus productos</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('provider.product.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Añadir producto</a>
            </div>
        </header>

        <?php if (session('success')): ?>
            <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-3 text-green-700"><?= session('success') ?></div>
        <?php endif; ?>
        <?php if (session('error')): ?>
            <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-red-700"><?= session('error') ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-gray-500 text-sm">Pedidos</div>
                <div class="text-2xl font-bold"><?= (int) ($totals['orders_count'] ?? 0) ?></div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-gray-500 text-sm">Subtotal vendido</div>
                <div class="text-2xl font-bold">$<?= number_format((float) ($totals['subtotal'] ?? 0), 2) ?></div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-gray-500 text-sm">Ingreso pagado</div>
                <div class="text-2xl font-bold">$<?= number_format((float) ($totals['paid_total'] ?? 0), 2) ?></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="md:col-span-2 bg-white rounded-lg shadow">
                <div class="p-4 border-b"><div class="text-lg font-semibold">Platillos más vendidos</div></div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ventas</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">⭐ Promedio</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php $list = ($topProducts ?? []); ?>
                        <?php if (!empty($list)): foreach ($list as $row): ?>
                            <tr>
                                <?php $p = $products->firstWhere('name', $row['name']); $display = $p ? $row['name'] : 'Producto eliminado'; ?>
                                <td class="px-4 py-2"><?= $display ?></td>
                                <td class="px-4 py-2"><?= (int) $row['qty'] ?></td>
                                <td class="px-4 py-2">$<?= number_format((float) $row['sales'], 2) ?></td>
                                <?php $pid = optional($products->firstWhere('name', $row['name']))?->id; $r = $pid ? ($ratingsByProduct[$pid] ?? null) : null; ?>
                                <td class="px-4 py-2"><?= $r ? ($r['avg'].' ('.$r['count'].')') : '—' ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" class="px-4 py-4 text-center text-gray-500">Sin datos</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-lg font-semibold mb-2">Calificaciones</div>
                <div class="text-sm text-gray-500">Promedio</div>
                <div class="text-3xl font-bold"><?= number_format((float) ($ratingStats['avg'] ?? 0), 2) ?> ⭐</div>
                <div class="text-sm text-gray-500 mt-1">Total: <?= (int) ($ratingStats['count'] ?? 0) ?></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Precio</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Disponible</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($products as $product)
                        <tr>
                            <td class="px-4 py-2">{{ $product->name }}</td>
                            <td class="px-4 py-2">${{ number_format($product->price, 2) }}</td>
                            <td class="px-4 py-2">{{ $product->is_available ? 'Sí' : 'No' }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <a href="{{ route('provider.product.edit', $product) }}" class="inline-flex items-center px-3 py-1 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Editar</a>
                                    <form action="{{ route('provider.product.destroy', $product) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-1 border border-red-600 text-red-700 rounded hover:bg-red-50">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">No tienes productos aún</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
