<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar producto</title>
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
</head>
<body class="bg-gray-50">
    <div class="max-w-3xl mx-auto p-6">
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
        <h1 class="text-2xl font-bold mb-4">Editar producto</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('provider.product.update', $product) }}" class="space-y-4" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', $product->name) }}" class="mt-1 w-full border rounded px-3 py-2" />
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Descripción</label>
                <textarea id="description" name="description" rows="4" class="mt-1 w-full border rounded px-3 py-2">{{ old('description', $product->description) }}</textarea>
            </div>
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700">Categoría</label>
                <select id="category" name="category" required class="mt-1 w-full border rounded px-3 py-2">
                    @php($cat = old('category', $product->category))
                    <option value="comida" {{ $cat==='comida'?'selected':'' }}>Comida</option>
                    <option value="limpieza" {{ $cat==='limpieza'?'selected':'' }}>Limpieza</option>
                    <option value="electronica" {{ $cat==='electronica'?'selected':'' }}>Electrónica</option>
                    <option value="herramientas" {{ $cat==='herramientas'?'selected':'' }}>Herramientas</option>
                    <option value="otros" {{ $cat==='otros'?'selected':'' }}>Otros</option>
                </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">Precio</label>
                    <input id="price" name="price" type="number" step="0.01" min="0.01" required value="{{ old('price', $product->price) }}" class="mt-1 w-full border rounded px-3 py-2" />
                </div>
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">Imagen del producto</label>
                    <input id="image" name="image" type="file" accept="image/*" class="mt-1 w-full border rounded px-3 py-2" />
                    @if ($product->image_url)
                        @php($img = $product->image_url)
                        @php($link = (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) ? $img : asset($img))
                        <p class="text-xs text-gray-500 mt-1">Actual: <a href="{{ $link }}" target="_blank" class="underline">ver imagen</a></p>
                    @endif
                </div>
            </div>

            <div>
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="is_available" value="0" />
                    <input type="checkbox" name="is_available" value="1" {{ old('is_available', $product->is_available) ? 'checked' : '' }} />
                    Disponible
                </label>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Actualizar</button>
                <a href="{{ route('provider.dashboard') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
