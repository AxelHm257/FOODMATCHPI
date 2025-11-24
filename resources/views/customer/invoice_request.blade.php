<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de factura</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-50">
    <div class="max-w-3xl mx-auto p-6">
        <nav class="mb-4 bg-white rounded-lg shadow p-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('orders.index') }}" class="px-3 py-2 border border-indigo-600 text-indigo-700 rounded hover:bg-indigo-50">Pedidos</a>
            </div>
            <div>
                <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="px-3 py-2 border border-red-600 text-red-700 rounded hover:bg-red-50">Cerrar sesión</a>
                <form id="logout-form" action="/logout" method="POST" class="hidden">@csrf</form>
            </div>
        </nav>

        <h1 class="text-2xl font-bold mb-4">Solicitar factura para pedido #{{ $order->id }}</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('orders.invoice.submit', $order) }}" class="space-y-4 bg-white rounded-lg shadow p-4">
            @csrf
            <div>
                <label for="tax_id" class="block text-sm font-medium text-gray-700">RFC/Cédula</label>
                <input id="tax_id" name="tax_id" type="text" required maxlength="32" class="mt-1 w-full border rounded px-3 py-2" />
            </div>
            <div>
                <label for="business_name" class="block text-sm font-medium text-gray-700">Razón social</label>
                <input id="business_name" name="business_name" type="text" required maxlength="255" class="mt-1 w-full border rounded px-3 py-2" />
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Correo de envío</label>
                <input id="email" name="email" type="email" required class="mt-1 w-full border rounded px-3 py-2" />
            </div>
            <div>
                <button class="px-4 py-2 border border-blue-600 text-blue-700 rounded hover:bg-blue-50">Solicitar</button>
                <a href="{{ route('orders.index') }}" class="ml-2 px-4 py-2 border border-gray-300 text-gray-800 rounded hover:bg-gray-50">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
