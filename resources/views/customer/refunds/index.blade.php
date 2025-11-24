<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis reembolsos</title>
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
            <a href="{{ route('customer.refunds.form') }}" class="px-3 py-2 border rounded {{ request()->routeIs('customer.refunds.form') ? 'bg-yellow-600 text-white border-yellow-600' : 'border-yellow-600 text-yellow-700 hover:bg-yellow-50' }}">Nueva solicitud</a>
        </div>
        <div>
            <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="px-3 py-2 border border-red-600 text-red-700 rounded hover:bg-red-50">Cerrar sesión</a>
            <form id="logout-form" action="/logout" method="POST" class="hidden">@csrf</form>
        </div>
    </nav>

    <h1 class="text-2xl font-bold mb-4">Mis reembolsos</h1>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-3 text-green-700">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pedido</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($refunds as $r)
                    <tr>
                        <td class="px-4 py-2">{{ $r->id }}</td>
                        <td class="px-4 py-2">{{ $r->order_reference ?? '-' }}</td>
                        @php
                            $rlabel = $r->status === 'pending' ? 'Pendiente' : ($r->status === 'approved' ? 'Aprobado' : ($r->status === 'rejected' ? 'Rechazado' : ucfirst($r->status)));
                            $rklass = $r->status === 'approved' ? 'text-green-700 border-green-600' : ($r->status === 'rejected' ? 'text-red-700 border-red-600' : 'text-yellow-700 border-yellow-600');
                        @endphp
                        <td class="px-4 py-2"><span class="inline-block px-2 py-1 border rounded {{ $rklass }}">{{ $rlabel }}</span></td>
                        <td class="px-4 py-2">{{ $r->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">No tienes reembolsos aún</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
