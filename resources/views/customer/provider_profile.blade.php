<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil del Proveedor</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <style>
        .map-placeholder{background:#f8fafc;border:1px solid #e5e7eb;border-radius:.5rem;padding:1rem}
    </style>
    </head>
<body class="bg-gray-50">
<div class="max-w-7xl mx-auto p-6">
    <nav class="mb-6 flex justify-between items-center bg-white p-4 fm-nav rounded-lg shadow-md">
        <div class="flex items-center gap-3">
            @php
                $navFiles = Storage::disk('public')->files('platform_logo');
                if (empty($navFiles)) {
                    $navFiles = Storage::disk('public')->files('provider_logos');
                    $navPreferred = array_values(array_filter($navFiles, function($f){ return preg_match('/foodmatch/i', basename($f)); }));
                    if(count($navPreferred)) { $navFiles = $navPreferred; }
                }
                $navImages = array_values(array_filter($navFiles, function($f){ return preg_match('/\.(png|jpg|jpeg|webp)$/i', $f); }));
                $navLogo = null;
                if(count($navImages)){
                    usort($navImages, function($a,$b){ return Storage::disk('public')->lastModified($a) <=> Storage::disk('public')->lastModified($b); });
                    $navLogo = end($navImages);
                }
                $navLogoUrl = $navLogo ? asset('storage/'.$navLogo) : null;
            @endphp
            @if($navLogoUrl)
                <a href="{{ route('customer.home') }}" class="inline-flex items-center">
                    <img src="{{ $navLogoUrl }}" alt="Logo" class="fm-nav-logo" />
                </a>
            @endif
            <a href="{{ route('customer.home') }}" class="px-3 py-2 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Inicio</a>
            <a href="{{ route('cart.index') }}" class="px-3 py-2 border border-green-600 text-green-700 rounded hover:bg-green-50">Carrito</a>
        </div>
        <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="px-3 py-2 border border-red-600 text-red-700 rounded hover:bg-red-50">Cerrar sesión</a>
        <form id="logout-form" action="/logout" method="POST" class="hidden">@csrf</form>
    </nav>

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-start gap-4">
            @php $logo = $provider->logo_url; @endphp
            @if(!empty($logo))
                <img src="{{ $logo }}" alt="Logo" class="w-20 h-20 rounded-full object-cover border" />
            @else
                <div class="w-20 h-20 rounded-full bg-gray-200 border flex items-center justify-center text-gray-500">🏪</div>
            @endif
            <div class="flex-1">
                <h1 class="text-3xl font-bold">{{ $provider->name }}</h1>
                <div class="mt-1 text-sm text-gray-600">{{ number_format((float)($stats['avg'] ?? 0),1) }}⭐ ({{ (int)($stats['count'] ?? 0) }})</div>
                <p class="mt-2 text-gray-700">{{ $provider->description }}</p>
                <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                    <div><span class="font-semibold">Contacto:</span> {{ $provider->contact ?? '—' }}</div>
                    <div><span class="font-semibold">Ubicación:</span> {{ $provider->location ?? '—' }}</div>
                    <div>
                        <span class="font-semibold">Coordenadas:</span>
                        @if ($provider->lat && $provider->lng)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $provider->lat }},{{ $provider->lng }}" target="_blank" class="underline text-indigo-700">{{ $provider->lat }}, {{ $provider->lng }}</a>
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                    <div><span class="font-semibold">Productos:</span> {{ $menuStats['count'] }}</div>
                    <div><span class="font-semibold">Precio promedio:</span> ${{ number_format($menuStats['avg_price'], 2) }}</div>
                    <div><span class="font-semibold">Rango de precios:</span> ${{ number_format($menuStats['min_price'], 2) }} – ${{ number_format($menuStats['max_price'], 2) }}</div>
                </div>
                @if (empty($userHasRated))
                    <form method="POST" action="{{ route('ratings.provider', $provider->id) }}" class="flex flex-col sm:flex-row sm:items-center gap-2 mt-4">
                        @csrf
                        <div class="flex items-center gap-1 rating" data-input="provider-stars-{{ $provider->id }}">
                            <button type="button" class="star text-lg" data-value="1">☆</button>
                            <button type="button" class="star text-lg" data-value="2">☆</button>
                            <button type="button" class="star text-lg" data-value="3">☆</button>
                            <button type="button" class="star text-lg" data-value="4">☆</button>
                            <button type="button" class="star text-lg" data-value="5">☆</button>
                        </div>
                        <input type="hidden" id="provider-stars-{{ $provider->id }}" name="stars" value="5" />
                        <input type="text" name="comment" placeholder="Escribe tu reseña" class="border border-gray-300 rounded px-2 py-1 w-full sm:w-80">
                        <button class="px-3 py-2 bg-indigo-600 text-white rounded w-full sm:w-auto">Calificar proveedor</button>
                    </form>
                @else
                    <div class="mt-4 text-sm text-gray-600">Ya calificaste a este proveedor.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="text-lg font-semibold">Galería</div>
            </div>
            @if($images->count())
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($images as $src)
                        <img src="{{ $src }}" alt="Foto" class="w-full h-32 object-cover rounded" />
                    @endforeach
                </div>
            @else
                <div class="text-gray-500">Sin fotos disponibles</div>
            @endif
            <div class="mt-6">
                <div class="text-lg font-semibold mb-2">Productos disponibles</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($menus as $menu)
                        <div class="border rounded-lg p-3">
                            <div class="flex items-center gap-3">
                                @php $img = $menu->image_url; @endphp
                                <img src="{{ $img ?: 'https://via.placeholder.com/120x90?text='.urlencode($menu->name) }}" class="w-24 h-16 rounded object-cover" alt="Imagen" />
                                <div class="flex-1">
                                    <div class="font-semibold">{{ $menu->name }}</div>
                                    <div class="text-sm text-gray-500">${{ number_format($menu->price, 2) }}</div>
                                    @php($ps = $productStatsById[$menu->id] ?? ['avg'=>0,'count'=>0])
                                    <div class="text-xs text-gray-600 mt-1">{{ number_format((float)$ps['avg'],1) }}⭐ ({{ (int)$ps['count'] }})</div>
                                </div>
                            </div>
                            <div class="mt-3 text-sm text-gray-700 hidden" id="details-{{ $menu->id }}">
                                <div><span class="font-semibold">Descripción:</span> {{ $menu->description ?: '—' }}</div>
                                <div><span class="font-semibold">Categoría:</span> {{ $menu->category ?? '—' }}</div>
                                <div><span class="font-semibold">Disponible:</span> {{ $menu->is_available ? 'Sí' : 'No' }}</div>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="px-3 py-1 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50" onclick="(function(){var el=document.getElementById('details-{{ $menu->id }}'); if(el){ el.classList.toggle('hidden'); } })()">Ver detalles</button>
                                <a href="{{ route('product.show', $menu->id) }}" class="ml-2 px-3 py-1 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Ver producto</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow p-6">
            <div class="text-lg font-semibold mb-4">Reseñas recientes</div>
            @forelse($ratings as $r)
                <div class="border-b pb-3 mb-3">
                    <div class="text-yellow-600 text-sm">{{ (int)$r->stars }}⭐</div>
                    <div class="text-sm text-gray-700">{{ $r->comment }}</div>
                    <div class="text-xs text-gray-500">{{ $r->created_at->diffForHumans() }}</div>
                </div>
            @empty
                <div class="text-gray-500">Sin reseñas</div>
            @endforelse
        </div>
    </div>
</div>
</body>
</html>
