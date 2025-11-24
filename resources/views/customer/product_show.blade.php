<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->name }} | Detalle del producto</title>
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
                <a href="{{ route('customer.home') }}" class="px-3 py-2 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Inicio</a>
                <a href="{{ route('cart.index') }}" class="px-3 py-2 border border-green-600 text-green-700 rounded hover:bg-green-50">Carrito</a>
            </div>
            <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="px-3 py-2 border border-red-600 text-red-700 rounded hover:bg-red-50">Cerrar sesión</a>
            <form id="logout-form" action="/logout" method="POST" class="hidden">@csrf</form>
        </nav>

        <div class="bg-white rounded-xl shadow p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-1">
                    @php
                        $img = $product->image_url ?? null;
                        $src = ($img && (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')))
                            ? $img
                            : ($img ? asset($img) : ('https://via.placeholder.com/400x250?text=' . urlencode($product->name)));
                    @endphp
                    <img src="{{ $src }}" alt="Imagen de {{ $product->name }}" class="w-full h-56 object-cover rounded-lg border" />
                </div>
                <div class="md:col-span-2">
                    <h1 class="text-3xl font-bold">{{ $product->name }}</h1>
                    <div class="text-lg text-green-700 font-semibold mt-1">${{ number_format($product->price, 2) }}</div>
                    <div class="mt-2 text-sm text-gray-600">{{ number_format((float)($stats['avg'] ?? 0),1) }}⭐ ({{ (int)($stats['count'] ?? 0) }})</div>
                    <div class="mt-4 text-gray-700">{{ $product->description ?: 'Sin descripción' }}</div>
                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        <div><span class="font-semibold">Categoría:</span> {{ $product->category ?? '—' }}</div>
                        <div><span class="font-semibold">Disponible:</span> {{ $product->is_available ? 'Sí' : 'No' }}</div>
                        <div><span class="font-semibold">Proveedor:</span> <a class="underline text-indigo-700" href="{{ route('provider.profile', $provider->id) }}">{{ $provider->name }}</a></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6 mt-6">
            <div class="flex items-start gap-4">
                @php $plogo = $provider->logo_url; @endphp
                @if(!empty($plogo))
                    <img src="{{ $plogo }}" alt="Logo" class="w-16 h-16 rounded-full object-cover border" />
                @else
                    <div class="w-16 h-16 rounded-full bg-gray-200 border flex items-center justify-center text-gray-500">🏪</div>
                @endif
                <div class="flex-1">
                    <div class="text-xl font-semibold">{{ $provider->name }}</div>
                    <div class="text-sm text-gray-600">{{ $provider->description ?? '—' }}</div>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
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
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6 mt-6">
            <div class="text-lg font-semibold mb-3">Calificaciones</div>
            @if (empty($userHasRated))
                <form method="POST" action="{{ route('ratings.product', $product->id) }}" class="flex flex-col sm:flex-row sm:items-center gap-2 mb-4">
                    @csrf
                    <div class="flex items-center gap-1 rating" data-input="product-stars-{{ $product->id }}-detail">
                        <button type="button" class="star text-lg" data-value="1">☆</button>
                        <button type="button" class="star text-lg" data-value="2">☆</button>
                        <button type="button" class="star text-lg" data-value="3">☆</button>
                        <button type="button" class="star text-lg" data-value="4">☆</button>
                        <button type="button" class="star text-lg" data-value="5">☆</button>
                    </div>
                    <input type="hidden" id="product-stars-{{ $product->id }}-detail" name="stars" value="5" />
                    <input type="text" name="comment" placeholder="Tu opinión" class="border border-gray-300 rounded px-2 py-1 w-full sm:w-80">
                    <button class="px-3 py-2 bg-indigo-600 text-white rounded w-full sm:w-auto">Calificar producto</button>
                </form>
            @endif
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
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.rating').forEach(function (container) {
          var inputId = container.getAttribute('data-input');
          var input = document.getElementById(inputId);
          var stars = container.querySelectorAll('.star');
          stars.forEach(function (star) {
            star.addEventListener('click', function () {
              var val = parseInt(star.getAttribute('data-value'), 10);
              if (input) input.value = val;
              stars.forEach(function(s){ s.textContent = '☆'; });
              for (var i = 0; i < val; i++) { stars[i].textContent = '★'; }
            });
          });
        });
      });
    </script>
</body>
</html>
