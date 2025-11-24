<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva solicitud de reembolso</title>
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
                    <a href="{{ route('customer.home') }}" class="inline-flex items-center">
                        <img src="{{ $logoUrl }}" alt="Logo" class="fm-nav-logo" />
                    </a>
                @endif
                <a href="{{ route('customer.refunds.index') }}" class="px-3 py-2 border rounded {{ request()->routeIs('customer.refunds.index') ? 'bg-yellow-600 text-white border-yellow-600' : 'border-yellow-600 text-yellow-700 hover:bg-yellow-50' }}">Mis reembolsos</a>
            <a href="{{ route('orders.index') }}" class="px-3 py-2 border rounded {{ request()->routeIs('orders.index') ? 'bg-blue-600 text-white border-blue-600' : 'border-blue-600 text-blue-700 hover:bg-blue-50' }}">Mis pedidos</a>
        </div>
        <div>
            <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="px-3 py-2 border border-red-600 text-red-700 rounded hover:bg-red-50">Cerrar sesión</a>
            <form id="logout-form" action="/logout" method="POST" class="hidden">@csrf</form>
        </div>
    </nav>

    <h1 class="text-2xl font-bold mb-4">Nueva solicitud de reembolso</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-red-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('customer.refunds.submit') }}" enctype="multipart/form-data" class="space-y-4 bg-white rounded-lg shadow p-4">
        @csrf
        <div>
            <label for="provider_id" class="block text-sm font-medium text-gray-700">Proveedor (opcional)</label>
            <select id="provider_id" name="provider_id" class="mt-1 w-full border rounded px-3 py-2">
                <option value="">Selecciona proveedor</option>
                @foreach ($providerOptions as $pid => $value)
                    <option value="{{ $pid }}" {{ isset($selectedProviderId) && $selectedProviderId==$pid ? 'selected' : '' }}>Proveedor #{{ $value }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="order_reference" class="block text-sm font-medium text-gray-700">Referencia de pedido (opcional)</label>
            <input id="order_reference" name="order_reference" type="text" maxlength="64" value="{{ $orderReference ?? '' }}" class="mt-1 w-full border rounded px-3 py-2" />
        </div>
        <div>
            <label for="reason" class="block text-sm font-medium text-gray-700">Motivo</label>
            <textarea id="reason" name="reason" rows="5" required minlength="10" class="mt-1 w-full border rounded px-3 py-2" placeholder="Describe el motivo según las políticas"></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Evidencias (imágenes, hasta 3)</label>
            <input type="file" name="evidences[]" accept="image/*" multiple required class="mt-1 w-full border rounded px-3 py-2" />
            <p class="text-xs text-gray-500 mt-1">Formatos: jpg, jpeg, png, webp. Máx 4MB cada una.</p>
        </div>
        <div>
            <button class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Enviar solicitud</button>
            <a href="{{ route('customer.refunds.index') }}" class="ml-2 px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>
