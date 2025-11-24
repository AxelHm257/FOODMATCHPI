<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\UserCart;
use App\Models\RefundRequest;
use Illuminate\Support\Facades\Storage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\InvoiceRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\PaymentReceiptMail;
use App\Notifications\OrderStatusNotification;
use App\Services\PaymentService;

class CartController extends Controller
{
    private function getCart(Request $request): array
    {
        return $request->session()->get('cart', []);
    }

    private function saveCart(Request $request, array $cart): void
    {
        $request->session()->put('cart', $cart);
        if (Auth::check()) {
            UserCart::updateOrCreate(
                ['user_id' => Auth::id()],
                ['cart' => json_encode($cart, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
            );
        }
    }

    public function index(Request $request)
    {
        $cart = $this->getCart($request);
        $commissionRate = (float) env('COMMISSION_RATE', 0.05);

        $subtotal = 0.0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['qty'];
        }
        $commission = round($subtotal * $commissionRate, 2);
        $total = round($subtotal, 2);

        return view('cart.index', compact('cart', 'subtotal', 'commission', 'total', 'commissionRate'));
    }

    public function add(Request $request, Product $product)
    {
        if (!$product->is_available) {
            return redirect()->route('cart.index')->with('error', 'El producto no está disponible.');
        }
        $request->validate([
            'extras' => 'nullable|array',
            'extras.*' => 'nullable|string|max:64',
            'note' => 'nullable|string|max:200',
        ]);
        $cart = $this->getCart($request);
        $id = (string) $product->id;
        if (!isset($cart[$id])) {
            $cart[$id] = [
                'product_id' => $product->id,
                'provider_id' => $product->provider_id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'qty' => 0,
                'type' => 'single',
                'extras' => [],
                'note' => null,
            ];
        }
        $extras = (array) $request->input('extras', []);
        $note = trim((string) $request->input('note', '')) ?: null;
        if (!empty($extras)) {
            $cart[$id]['extras'] = array_values($extras);
        }
        if ($note !== null) {
            $cart[$id]['note'] = $note;
        }
        $cart[$id]['qty'] = min(99, (int) $cart[$id]['qty'] + 1);
        $this->saveCart($request, $cart);

        return redirect()->route('cart.index')->with('success', 'Producto añadido al carrito.');
    }

    public function remove(Request $request, Product $product)
    {
        $cart = $this->getCart($request);
        $id = (string) $product->id;
        if (isset($cart[$id])) {
            $cart[$id]['qty'] -= 1;
            if ($cart[$id]['qty'] <= 0) {
                unset($cart[$id]);
            }
            $this->saveCart($request, $cart);
        }
        return redirect()->route('cart.index')->with('success', 'Producto eliminado del carrito.');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'qty' => 'required|integer|min:0|max:99',
        ]);
        $cart = $this->getCart($request);
        $id = (string) $product->id;
        if (!isset($cart[$id])) {
            return redirect()->route('cart.index')->with('error', 'El producto no está en el carrito.');
        }
        $qty = (int) $validated['qty'];
        if ($qty <= 0) {
            unset($cart[$id]);
        } else {
            $cart[$id]['qty'] = $qty;
        }
        $this->saveCart($request, $cart);
        return redirect()->route('cart.index')->with('success', 'Cantidad actualizada.');
    }

    public function delete(Request $request, Product $product)
    {
        $cart = $this->getCart($request);
        $id = (string) $product->id;
        if (isset($cart[$id])) {
            unset($cart[$id]);
            $this->saveCart($request, $cart);
        }
        return redirect()->route('cart.index')->with('success', 'Producto eliminado.');
    }

    public function deleteMix(Request $request, string $key)
    {
        $cart = $this->getCart($request);
        if (isset($cart[$key]) && (($cart[$key]['type'] ?? null) === 'mix')) {
            unset($cart[$key]);
            $this->saveCart($request, $cart);
            return redirect()->route('cart.index')->with('success', 'Combo eliminado.');
        }
        return redirect()->route('cart.index')->with('error', 'No se encontró el combo.');
    }

    public function clear(Request $request)
    {
        $request->session()->forget('cart');
        if (Auth::check()) {
            UserCart::updateOrCreate(
                ['user_id' => Auth::id()],
                ['cart' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
            );
        }
        return redirect()->route('cart.index')->with('success', 'Carrito vaciado.');
    }

    public function checkout(Request $request)
    {
        $cart = $this->getCart($request);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Tu carrito está vacío.');
        }

        $commissionRate = (float) env('COMMISSION_RATE', 0.05);
        $subtotal = 0.0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['qty'];
        }
        $commission = round($subtotal * $commissionRate, 2);
        $total = round($subtotal, 2);

        return view('cart.checkout', compact('cart', 'subtotal', 'commission', 'total', 'commissionRate'));
    }

    public function place(Request $request)
    {
        $cart = $this->getCart($request);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Tu carrito está vacío.');
        }
        // Sanitizar y validar cada ítem
        foreach ($cart as $key => $item) {
            $qty = (int) ($item['qty'] ?? 0);
            if ($qty <= 0 || $qty > 99) {
                unset($cart[$key]);
                continue;
            }
            $p = Product::find($item['product_id'] ?? 0);
            if (!$p || !$p->is_available) {
                unset($cart[$key]);
                continue;
            }
            // Forzar precio desde BD para evitar manipulación de sesión
            $cart[$key]['price'] = (float) $p->price;
            $cart[$key]['name'] = $p->name;
            $cart[$key]['provider_id'] = $p->provider_id;
        }
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'No hay productos válidos para generar el pedido.');
        }

        $commissionRate = (float) env('COMMISSION_RATE', 0.05);
        $grouped = [];
        foreach ($cart as $item) {
            $grouped[(string) $item['provider_id']][] = $item;
        }

        $created = [];
        foreach ($grouped as $providerId => $items) {
            $subtotal = 0.0;
            foreach ($items as $i) {
                $subtotal += $i['price'] * $i['qty'];
            }
            $commission = round($subtotal * $commissionRate, 2);
            if ($subtotal <= 0) { continue; }
            $order = Order::create([
                'user_id' => Auth::id(),
                'provider_id' => (int) $providerId,
                'status' => 'pending',
                'subtotal' => round($subtotal, 2),
                'commission' => $commission,
                'total' => round($subtotal, 2),
            ]);
            foreach ($items as $i) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $i['product_id'],
                    'name' => $i['name'],
                    'qty' => $i['qty'],
                    'price' => $i['price'],
                    'extras' => json_encode($i['extras'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'note' => $i['note'] ?? null,
                ]);
            }
            $created[] = $order->id;
            try {
                if (Auth::user()) {
                    Auth::user()->notify(new OrderStatusNotification($order, 'Pedido creado', 'Tu pedido ha sido creado correctamente.'));
                }
            } catch (\Throwable $e) {
                Log::warning('Notif place: '.$e->getMessage());
            }
        }

        $request->session()->forget('cart');
        UserCart::updateOrCreate(
            ['user_id' => Auth::id()],
            ['cart' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );

        return redirect()->route('orders.index')->with('success', 'Pedido generado.');
    }

    public function ordersIndex(Request $request)
    {
        $orders = Order::where('user_id', Auth::id())
            ->latest()
            ->get();
        $notifications = Auth::user() ? Auth::user()->notifications()->latest()->limit(10)->get() : collect();
        return view('customer.orders', compact('orders', 'notifications'));
    }

    public function pay(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            return redirect()->route('orders.index')->with('error', 'Acceso denegado.');
        }
        $order->status = 'paid';
        $order->save();
        try {
            if (Auth::user()) {
                Auth::user()->notify(new OrderStatusNotification($order, 'Pedido pagado', 'Tu pago fue registrado con éxito.'));
            }
        } catch (\Throwable $e) {
            Log::warning('Notif pay: '.$e->getMessage());
        }
        try {
            if (Auth::user() && Auth::user()->email) {
                Mail::to(Auth::user()->email)->send(new PaymentReceiptMail($order));
            }
            $inv = InvoiceRequest::where('order_id', $order->id)->latest()->first();
            if ($inv && $inv->email) {
                Mail::to($inv->email)->send(new PaymentReceiptMail($order));
            }
        } catch (\Throwable $e) {
            Log::warning('Error enviando comprobante: '.$e->getMessage());
        }
        return redirect()->route('orders.index')->with('success', 'Pago confirmado.');
    }

    public function startGatewayPayment(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            return redirect()->route('orders.index')->with('error', 'Acceso denegado.');
        }
        $svc = new PaymentService();
        $intent = $svc->createIntent((float) $order->total, (string) env('APP_CURRENCY', 'MXN'), [
            'order_id' => (string) $order->id,
            'description' => 'Pago pedido #'.$order->id,
        ]);
        $order->payment_provider = config('services.payments.provider');
        $order->external_payment_id = $intent['id'] ?? null;
        $order->payment_status = 'created';
        $order->save();
        return redirect()->away($intent['redirect_url'] ?? route('orders.index'));
    }

    public function pause(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            return redirect()->route('orders.index')->with('error', 'Acceso denegado.');
        }
        if ($order->status === 'paid') {
            return redirect()->route('orders.index')->with('error', 'Los pedidos pagados no se pueden pausar.');
        }
        if (in_array($order->status, ['cancelled', 'refunded'], true)) {
            return redirect()->route('orders.index')->with('error', 'No disponible para este estado.');
        }
        $request->validate([
            'until' => 'nullable|date',
        ]);
        $until = $request->input('until');
        $order->status = 'paused';
        if ($until) {
            $date = \Carbon\Carbon::parse($until)->startOfDay();
            if ($date->lt(now()->startOfDay())) {
                return redirect()->route('orders.index')->with('error', 'La fecha para pausar debe ser futura.');
            }
            if ($date->gt(now()->addDays(180))) {
                return redirect()->route('orders.index')->with('error', 'La pausa no puede exceder 180 días.');
            }
            $order->paused_until = $date->toDateString();
        } else {
            $order->paused_until = null;
        }
        $order->save();
        try {
            if (Auth::user()) {
                $note = $order->paused_until ? ('hasta '.$order->paused_until) : 'hasta nuevo aviso';
                Auth::user()->notify(new OrderStatusNotification($order, 'Pedido pausado', 'Tu pedido ha sido pausado '.$note.'.'));
            }
        } catch (\Throwable $e) {
            Log::warning('Notif pause: '.$e->getMessage());
        }
        return redirect()->route('orders.index')->with('success', 'Entrega pausada.');
    }

    public function reschedule(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            return redirect()->route('orders.index')->with('error', 'Acceso denegado.');
        }
        if (!in_array($order->status, ['pending', 'paused'], true)) {
            return redirect()->route('orders.index')->with('error', 'Reprogramar solo está disponible para pendientes o pausados.');
        }
        $request->validate([
            'delivery_date' => 'required|date',
        ]);
        $date = \Carbon\Carbon::parse($request->input('delivery_date'))->startOfDay();
        if ($date->lt(now()->startOfDay())) {
            return redirect()->route('orders.index')->with('error', 'La entrega debe ser en una fecha futura.');
        }
        if ($date->gt(now()->addDays(90))) {
            return redirect()->route('orders.index')->with('error', 'La entrega no puede ser más allá de 90 días.');
        }
        $order->delivery_date = $date->toDateString();
        if ($order->status === 'paused') {
            $order->status = 'pending';
            $order->paused_until = null;
        }
        $order->save();
        try {
            if (Auth::user()) {
                Auth::user()->notify(new OrderStatusNotification($order, 'Entrega reprogramada', 'Tu pedido fue reprogramado para '.$order->delivery_date.'.'));
            }
        } catch (\Throwable $e) {
            Log::warning('Notif reschedule: '.$e->getMessage());
        }
        return redirect()->route('orders.index')->with('success', 'Entrega reprogramada.');
    }

    public function invoiceForm(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            return redirect()->route('orders.index');
        }
        return view('customer.invoice_request', compact('order'));
    }

    public function invoiceSubmit(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            return redirect()->route('orders.index');
        }
        $request->validate([
            'tax_id' => 'required|string|max:32|regex:/^[A-Za-z0-9\-]+$/',
            'business_name' => "required|string|max:255|regex:/^[\p{L}\d\s'\-\.]+$/u",
            'email' => 'required|email:rfc,dns',
        ]);
        InvoiceRequest::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'tax_id' => $request->tax_id,
            'business_name' => $request->business_name,
            'email' => $request->email,
            'status' => 'requested',
        ]);
        try {
            if ($order->status === 'paid') {
                Mail::to($request->email)->send(new PaymentReceiptMail($order));
            }
        } catch (\Throwable $e) {
            Log::warning('Error enviando comprobante (factura): '.$e->getMessage());
        }
        return redirect()->route('orders.index')->with('success', 'Factura solicitada.');
    }

    // combinación de productos removida según requerimiento

    public function refundForm(Request $request)
    {
        $orderId = (int) $request->query('order', 0);
        $providerOptions = [];
        $selectedProviderId = null;
        $orderReference = null;
        $refunds = RefundRequest::where('user_id', Auth::id())->latest()->get();
        if ($orderId > 0) {
            $order = Order::where('id', $orderId)->where('user_id', Auth::id())->first();
            if ($order) {
                $selectedProviderId = $order->provider_id;
                $orderReference = (string) $order->id;
                $providerOptions[$order->provider_id] = $order->provider_id;
                $request->session()->put('refund_order_reference', $order->id);
            }
        }
        return view('customer.refunds.create', compact('providerOptions', 'selectedProviderId', 'orderReference'));
    }

    public function submitRefund(Request $request)
    {
        $request->validate([
            'provider_id' => 'nullable|integer',
            'order_reference' => 'nullable|string|max:64',
            'reason' => 'required|string|min:10|max:2000',
            'evidences.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $orderRef = (string) ($request->input('order_reference') ?: (string) $request->session()->get('refund_order_reference', ''));

        $files = (array) $request->file('evidences');
        if (empty($files)) {
            return back()->with('error', 'Debes adjuntar al menos una imagen como evidencia.');
        }
        $paths = [];
        foreach ($files as $file) {
            if ($file) {
                $name = 'refunds/'.Auth::id().'-'.uniqid().'.'.$file->getClientOriginalExtension();
                $stored = $file->storeAs('', $name, 'public');
                $paths[] = Storage::url($stored);
            }
        }

        $rr = RefundRequest::create([
            'user_id' => Auth::id(),
            'provider_id' => $request->input('provider_id'),
            'order_reference' => $orderRef ?: null,
            'reason' => $request->input('reason'),
            'status' => 'pending',
            'evidences' => json_encode($paths, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        if (!empty($orderRef)) {
            $order = Order::where('id', (int) $orderRef)->where('user_id', Auth::id())->first();
            if ($order) {
                $order->status = 'refund_requested';
                $order->save();
            }
        }

        $request->session()->forget('refund_order_reference');

        return redirect()->route('customer.refunds.index')->with('success', 'Solicitud enviada. Te notificaremos la respuesta.');
    }

    public function refundsIndex(Request $request)
    {
        $refunds = RefundRequest::where('user_id', Auth::id())->latest()->get();
        return view('customer.refunds.index', compact('refunds'));
    }

    public function cancel(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            return redirect()->route('orders.index')->with('error', 'Acceso denegado.');
        }
        if ($order->status === 'cancelled') {
            return redirect()->route('orders.index')->with('error', 'El pedido ya está cancelado.');
        }
        if ($order->status === 'paid') {
            return redirect()->route('orders.index')->with('error', 'Pedido pagado. Solicita reembolso.');
        }
        $order->status = 'cancelled';
        $order->save();
        try {
            if (Auth::user()) {
                Auth::user()->notify(new OrderStatusNotification($order, 'Pedido cancelado', 'Se canceló tu pedido a solicitud.'));
            }
        } catch (\Throwable $e) {
            Log::warning('Notif cancel: '.$e->getMessage());
        }
        return redirect()->route('orders.index')->with('success', 'Pedido cancelado.');
    }

    public function receipt(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            return redirect()->route('orders.index');
        }
        return view('emails.payment_receipt', ['order' => $order->load('items')]);
    }

    public function receiptResend(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            return redirect()->route('orders.index')->with('error', 'Acceso denegado.');
        }
        try {
            if (Auth::user() && Auth::user()->email) {
                Mail::to(Auth::user()->email)->send(new PaymentReceiptMail($order));
            }
        } catch (\Throwable $e) {
            Log::warning('Error reenviando comprobante: '.$e->getMessage());
            return redirect()->route('orders.index')->with('error', 'No se pudo reenviar el comprobante.');
        }
        return redirect()->route('orders.index')->with('success', 'Comprobante reenviado a tu correo.');
    }
}
