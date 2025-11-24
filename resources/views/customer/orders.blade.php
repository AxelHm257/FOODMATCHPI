<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis pedidos</title>
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
                <a href="{{ route('customer.refunds.index') }}" class="px-3 py-2 border border-yellow-600 text-yellow-700 rounded hover:bg-yellow-50">Mis reembolsos</a>
            </div>
            <div>
                <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="px-3 py-2 border border-red-600 text-red-700 rounded hover:bg-red-50">Cerrar sesión</a>
                <form id="logout-form" action="/logout" method="POST" class="hidden">@csrf</form>
            </div>
        </nav>

        <h1 class="text-2xl font-bold mb-4">Mis pedidos</h1>

        @if(isset($notifications) && $notifications->count() > 0)
            <div class="mb-4 bg-white rounded-lg shadow p-3">
                <div class="font-semibold mb-2">Notificaciones</div>
                <ul class="space-y-2 text-sm">
                    @foreach($notifications as $n)
                        @php $data = (array) $n->data; @endphp
                        <li class="flex items-center justify-between">
                            <span>{{ $data['title'] ?? 'Actualización' }} — {{ $data['message'] ?? '' }}</span>
                            <span class="text-gray-500">{{ optional($n->created_at)->format('d/m/Y H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

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
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $order)
                        <tr>
                            <td class="px-4 py-2">{{ $order->id }}</td>
                            <td class="px-4 py-2">
                                @php
                                    $status = $order->status;
                                    $label = $status === 'pending' ? 'Pendiente' : ($status === 'paid' ? 'Pagado' : ($status === 'paused' ? 'Pausado' : ($status === 'refund_requested' ? 'Reembolso solicitado' : ($status === 'cancelled' ? 'Cancelado' : ($status === 'refunded' ? 'Reembolsado' : ucfirst($status))))));
                                    $klass = $status === 'paid' ? 'bg-green-100 text-green-700' : ($status === 'cancelled' ? 'bg-red-100 text-red-700' : ($status === 'refund_requested' ? 'bg-yellow-100 text-yellow-700' : 'bg-yellow-100 text-yellow-700'));
                                @endphp
                                <span class="inline-block px-2 py-1 rounded {{ $klass }}">{{ $label }}</span>
                            </td>
                            <td class="px-4 py-2">${{ number_format($order->subtotal, 2) }}</td>
                            <td class="px-4 py-2">${{ number_format($order->total, 2) }}</td>
                            <td class="px-4 py-2">
                                <div class="flex items-center gap-2">
                                    @if ($order->status === 'pending')
                                        <form method="POST" action="{{ route('orders.pay', $order) }}" class="inline">
                                            @csrf
                                            <button class="px-3 py-1 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Pagar</button>
                                        </form>
                                        <form method="POST" action="{{ route('orders.cancel', $order) }}" class="inline" onsubmit="return confirm('¿Cancelar este pedido?');">
                                            @csrf
                                            <button class="px-3 py-1 border border-red-600 text-red-700 rounded hover:bg-red-50">Cancelar</button>
                                        </form>
                                    @elseif ($order->status === 'paid')
                                        <a href="{{ route('orders.receipt', $order) }}" target="_blank" class="px-3 py-1 border border-blue-600 text-blue-700 rounded hover:bg-blue-50">Descargar comprobante</a>
                                        <form method="POST" action="{{ route('orders.receipt.resend', $order) }}" class="inline">
                                            @csrf
                                            <button class="px-3 py-1 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Reenviar comprobante</button>
                                        </form>
                                        <a href="{{ route('customer.refunds.form', ['order' => $order->id]) }}" class="px-3 py-1 border border-yellow-600 text-yellow-700 rounded hover:bg-yellow-50">Reembolso</a>
                                    @elseif ($order->status === 'refund_requested')
                                        <a href="{{ route('customer.refunds.index') }}" class="px-3 py-1 border border-yellow-600 text-yellow-700 rounded hover:bg-yellow-50">Ver solicitud</a>
                                    @else
                                        <span class="text-gray-500">Sin acciones</span>
                                    @endif

                                    @if (in_array($order->status, ['pending','paused']))
                                        <form method="POST" action="{{ route('orders.pause', $order) }}" class="inline">
                                            @csrf
                                            <button class="px-3 py-1 border border-gray-600 text-gray-700 rounded hover:bg-gray-50">Pausar</button>
                                        </form>
                                        <form method="POST" action="{{ route('orders.reschedule', $order) }}" class="inline flex items-center gap-2">
                                            @csrf
                                            <input type="date" name="delivery_date" class="border rounded px-2 py-1 text-sm" value="{{ $order->delivery_date ?? '' }}" />
                                            <button class="px-3 py-1 border border-blue-600 text-blue-700 rounded hover:bg-blue-50">Reprogramar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5" class="px-4 py-2">
                                <div class="text-sm text-gray-700">Productos:</div>
                                <ul class="list-disc pl-6 text-sm text-gray-600">
                                    @foreach($order->items as $it)
                                        <li>{{ $it->name }} × {{ $it->qty }} — ${{ number_format($it->price * $it->qty, 2) }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">Aún no tienes pedidos</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
