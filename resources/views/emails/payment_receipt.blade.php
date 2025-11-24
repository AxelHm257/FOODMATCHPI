<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Comprobante de pago - Pedido #{{ $order->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        .box { border: 1px solid #ddd; padding: 12px; border-radius: 6px; }
        .header { margin-bottom: 12px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f7f7f7; }
        .totals td { font-weight: bold; }
    </style>
    </head>
<body>
    <div class="header">
        <h2>Comprobante de pago</h2>
        <div class="muted">Pedido #{{ $order->id }} · {{ $order->created_at?->format('Y-m-d H:i') }}</div>
    </div>

    <div class="box" style="margin-bottom:12px;">
        <div>Estado: <strong>{{ ucfirst($order->status) }}</strong></div>
        <div>Subtotal: <strong>${{ number_format($order->subtotal, 2) }}</strong></div>
        <div>Comisión: <strong>${{ number_format($order->commission, 2) }}</strong></div>
        <div>Total: <strong>${{ number_format($order->total, 2) }}</strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->qty }}</td>
                    <td>${{ number_format($item->price, 2) }}</td>
                    <td>${{ number_format($item->price * $item->qty, 2) }}</td>
                </tr>
                @if (!empty($item->extras) || !empty($item->note))
                    <tr>
                        <td colspan="4" class="muted">
                            @php($extras = is_string($item->extras) ? json_decode($item->extras, true) : $item->extras)
                            @if (!empty($extras))
                                Extras: {{ implode(', ', (array) $extras) }}
                            @endif
                            @if (!empty($item->note))
                                @if (!empty($extras)) · @endif
                                Nota: {{ $item->note }}
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <td colspan="3">Total</td>
                <td>${{ number_format($order->total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <p class="muted" style="margin-top:10px;">Este comprobante se genera automáticamente. Gracias por tu compra.</p>
</body>
</html>
