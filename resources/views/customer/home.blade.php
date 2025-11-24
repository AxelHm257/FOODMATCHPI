<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menús Disponibles | FoodMatch</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    </head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto p-4 sm:p-8">
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
                <a href="{{ route('cart.index') }}" class="px-3 py-2 border rounded {{ request()->routeIs('cart.index') ? 'bg-green-600 text-white border-green-600' : 'border-green-600 text-green-700 hover:bg-green-50' }}">Carrito</a>
                <a href="{{ route('customer.refunds.index') }}" class="px-3 py-2 border rounded {{ request()->routeIs('customer.refunds.index') ? 'bg-yellow-600 text-white border-yellow-600' : 'border-yellow-600 text-yellow-700 hover:bg-yellow-50' }}">Mis reembolsos</a>
                <a href="{{ route('orders.index') }}" class="px-3 py-2 border rounded {{ request()->routeIs('orders.index') ? 'bg-blue-600 text-white border-blue-600' : 'border-blue-600 text-blue-700 hover:bg-blue-50' }}">Mis pedidos</a>
            </div>
            <a href="/logout"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="px-3 py-2 border border-red-600 text-red-700 rounded hover:bg-red-50">
                Cerrar Sesión
            </a>
            <form id="logout-form" action="/logout" method="POST" class="hidden">
                @csrf
            </form>
        </nav>
        <?php if (session('success')): ?>
            <div class="mb-6 rounded-md bg-green-50 border border-green-200 p-3 text-green-700">{{ session('success') }}</div>
        <?php endif; ?>
        <?php if (session('error')): ?>
            <div class="mb-6 rounded-md bg-red-50 border border-red-200 p-3 text-red-700">{{ session('error') }}</div>
        <?php endif; ?>

        <h2 class="text-3xl font-bold text-gray-800 mb-4 flex items-center">
            <span class="mr-3">🍽️</span> Explora los Productos Disponibles
        </h2>
        <div class="mb-8 flex items-center gap-2">
            @php $v = in_array(($view ?? 'cards'), ['cards','list']) ? $view : 'cards'; @endphp
            <a href="{{ route('customer.home', ['view' => 'cards']) }}" class="px-3 py-1.5 rounded border {{ $v==='cards' ? 'bg-indigo-600 text-white border-indigo-600' : 'text-indigo-700 border-indigo-600 hover:bg-indigo-50' }}">Tarjetas</a>
            <a href="{{ route('customer.home', ['view' => 'list']) }}" class="px-3 py-1.5 rounded border {{ $v==='list' ? 'bg-indigo-600 text-white border-indigo-600' : 'text-indigo-700 border-indigo-600 hover:bg-indigo-50' }}">Lista</a>
        </div>


        @php $v = $view ?? 'cards'; @endphp
        @if ($v === 'cards')
            @forelse ($providersWithMenus as $providerName => $menus)
                <div class="bg-white shadow-xl rounded-xl p-6 sm:p-8 mb-12 border-t-6 border-indigo-600">
                    <h3 class="text-3xl font-bold text-gray-900 mb-2 flex items-center">
                        <span class="mr-3 text-indigo-600">🏪</span> <a href="{{ route('provider.profile', $providerStatsByName[$providerName]['id'] ?? null) }}" class="hover:underline">{{ $providerName }}</a>
                        @php $ps = ($providerStatsByName[$providerName] ?? null); @endphp
                        @if($ps)
                            <span class="ml-3 text-sm text-gray-600">{{ number_format((float)($ps['avg'] ?? 0), 1) }}⭐ ({{ (int)($ps['count'] ?? 0) }})</span>
                        @endif
                    </h3>
                    @php $providerId = ($providerStatsByName[$providerName]['id'] ?? null); @endphp
                    @if($providerId && empty($providerRatedByUser[$providerId]))
                        <form method="POST" action="{{ route('ratings.provider', $providerId) }}" class="flex flex-col md:flex-row md:items-center gap-2 mb-4">
                            @csrf
                            <div class="flex items-center gap-1 rating" data-input="provider-stars-{{ $providerId }}">
                                <button type="button" class="star text-lg" data-value="1">☆</button>
                                <button type="button" class="star text-lg" data-value="2">☆</button>
                                <button type="button" class="star text-lg" data-value="3">☆</button>
                                <button type="button" class="star text-lg" data-value="4">☆</button>
                                <button type="button" class="star text-lg" data-value="5">☆</button>
                            </div>
                            <input type="hidden" id="provider-stars-{{ $providerId }}" name="stars" value="5" />
                            <input type="text" name="comment" placeholder="Comentario" class="border border-gray-300 rounded px-2 py-1 w-full md:w-64">
                            <button class="px-3 py-2 bg-indigo-600 text-white rounded w-full md:w-auto">Calificar proveedor</button>
                        </form>
                    @elseif($providerId)
                        <div class="mb-4 text-sm text-gray-600">Ya calificaste a este proveedor.</div>
                    @endif
                    <p class="text-gray-600 mb-8 border-b pb-4">
                        Menú completo disponible para ordenar. ¡Encuentra tu próximo antojo!
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        @foreach ($menus as $menu)
                            <div class="border border-gray-200 rounded-xl p-5 bg-white shadow-sm hover:shadow-lg transition duration-200 flex flex-col">
                                <div class="mb-4 overflow-hidden rounded-lg">
                                    @php
                                        $img = $menu->image_url ?? null;
                                        $src = ($img && (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')))
                                            ? $img
                                            : ($img ? asset($img) : ('https://via.placeholder.com/400x250?text=Platillo+' . urlencode($menu->name)));
                                    @endphp
                                    <img src="{{ $src }}" alt="Imagen de {{ $menu->name }}" class="w-full h-40 object-cover">
                                </div>
                                <div class="flex-grow">
                                    <h4 class="text-xl font-bold text-gray-800 mb-1">{{ $menu->name }}</h4>
                                    <p class="text-sm text-gray-500 line-clamp-2 mb-3 h-10">{{ $menu->description }}</p>
                                </div>
                                <div class="mt-4 pt-3 border-t border-gray-100 space-y-2">
                                    <p class="text-2xl font-extrabold text-green-600 mb-3">${{ number_format($menu->price, 2) }}</p>
                                    <form method="POST" action="{{ route('cart.add', $menu->id) }}">
                                        @csrf
                                        <button class="w-full bg-indigo-600 text-white font-semibold py-3 rounded-lg hover:bg-indigo-700 transition">🛒 Añadir al carrito</button>
                                    </form>
                                    @php $ps = ($productStatsById[$menu->id] ?? null); @endphp
                                    <div class="text-sm text-gray-600">{{ $ps ? number_format((float)$ps['avg'],1) : '0.0' }}⭐ ({{ $ps['count'] ?? 0 }})</div>
                                    @if (empty($productRatedByUser[$menu->id]))
                                        <form method="POST" action="{{ route('ratings.product', $menu->id) }}" class="flex flex-col sm:flex-row sm:items-center gap-2">
                                            @csrf
                                            <div class="flex items-center gap-1 rating" data-input="product-stars-{{ $menu->id }}">
                                                <button type="button" class="star text-base" data-value="1">☆</button>
                                                <button type="button" class="star text-base" data-value="2">☆</button>
                                                <button type="button" class="star text-base" data-value="3">☆</button>
                                                <button type="button" class="star text-base" data-value="4">☆</button>
                                                <button type="button" class="star text-base" data-value="5">☆</button>
                                            </div>
                                            <input type="hidden" id="product-stars-{{ $menu->id }}" name="stars" value="5" />
                                            <input type="text" name="comment" placeholder="Comentario" class="border border-gray-300 rounded px-2 py-1 w-full sm:flex-1">
                                            <button class="px-3 py-2 bg-gray-800 text-white rounded w-full sm:w-auto">Calificar</button>
                                        </form>
                                    @else
                                        <div class="text-sm text-gray-600">Ya calificaste este producto.</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center py-20 bg-white rounded-xl shadow-lg border-2 border-dashed border-gray-300">
                    <p class="text-gray-600 text-xl font-medium">Parece que aún no hay platillos disponibles. ¡Vuelve pronto! ⏳</p>
                    <p class="text-gray-400 mt-2">Estamos esperando que nuestros proveedores actualicen sus menús.</p>
                </div>
            @endforelse
        @elseif ($v === 'list')
            @php $hasMenus = collect($providersWithMenus)->flatten(1)->count() > 0; @endphp
            @if (!$hasMenus)
                <div class="text-center py-20 bg-white rounded-xl shadow-lg border-2 border-dashed border-gray-300">
                    <p class="text-gray-600 text-xl font-medium">Parece que aún no hay platillos disponibles. ¡Vuelve pronto! ⏳</p>
                    <p class="text-gray-400 mt-2">Estamos esperando que nuestros proveedores actualicen sus menús.</p>
                </div>
            @else
                <div class="bg-white rounded-xl shadow p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Precio</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($providersWithMenus as $providerName => $menus)
                                @foreach ($menus as $menu)
                                    <tr>
                                        <td class="px-4 py-2">{{ $providerName }}</td>
                                        <td class="px-4 py-2">{{ $menu->name }}</td>
                                        <td class="px-4 py-2">${{ number_format($menu->price, 2) }}</td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center gap-2">
                                                <form method="POST" action="{{ route('cart.add', $menu->id) }}" class="inline">
                                                    @csrf
                                                    <button class="px-3 py-1 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Añadir</button>
                                                </form>
                                                @if (empty($productRatedByUser[$menu->id]))
                                                    <form method="POST" action="{{ route('ratings.product', $menu->id) }}" class="inline-flex flex-col sm:flex-row sm:items-center gap-2">
                                                        @csrf
                                                        <div class="flex items-center gap-1 rating" data-input="product-stars-{{ $menu->id }}-list">
                                                            <button type="button" class="star text-base" data-value="1">☆</button>
                                                            <button type="button" class="star text-base" data-value="2">☆</button>
                                                            <button type="button" class="star text-base" data-value="3">☆</button>
                                                            <button type="button" class="star text-base" data-value="4">☆</button>
                                                            <button type="button" class="star text-base" data-value="5">☆</button>
                                                        </div>
                                                        <input type="hidden" id="product-stars-{{ $menu->id }}-list" name="stars" value="5" />
                                                        <input type="text" name="comment" placeholder="Comentario" class="border rounded px-2 py-1 text-sm w-full sm:w-40">
                                                        <button class="px-2 py-1 bg-gray-800 text-white rounded text-sm w-full sm:w-auto">Calificar</button>
                                                    </form>
                                                @else
                                                    <span class="text-xs text-gray-600">Ya calificaste este producto.</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
        <div class="fixed bottom-6 right-6">
            <a href="{{ route('cart.index') }}" class="px-4 py-3 bg-green-600 text-white rounded-lg shadow hover:bg-green-700">Ver carrito</a>
        </div>
    </div>
</body>
</html>
