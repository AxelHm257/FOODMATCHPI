<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Provider;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProviderProductController extends Controller
{
    public function __construct()
    {
        // Se recomienda confiar en el middleware 'auth' y el middleware de rol
        // definidos en el archivo routes/web.php para proteger todas las rutas de este controlador.
        // Mantenemos solo la verificación de perfil de proveedor.
        $this->middleware(function ($request, $next) {

            // Si el usuario autenticado no tiene un perfil de proveedor asociado,
            // lo redirigimos a la ruta principal de la aplicación.
            if (Auth::check() && !Auth::user()->provider) {
                abort(403);
            }
            return $next($request);
        });
    }

    /**
     * Muestra la lista de productos del proveedor autenticado.
     */
    public function index()
    {
        $provider = Auth::user()->provider;
        $products = Product::where('provider_id', $provider->id)->latest()->get();

        $orders = Order::where('provider_id', $provider->id)->get();
        $totals = [
            'orders_count' => $orders->count(),
            'subtotal' => (float) $orders->sum('subtotal'),
            'paid_total' => (float) $orders->where('status', 'paid')->sum('total'),
        ];
        $orderIds = $orders->pluck('id');
        $items = OrderItem::whereIn('order_id', $orderIds)->get();
        $topProducts = $items->groupBy('product_id')->map(function ($group) {
            $name = optional($group->first())->name;
            $qty = (int) $group->sum('qty');
            $sales = 0.0;
            foreach ($group as $it) { $sales += ($it->price * $it->qty); }
            return ['name' => $name, 'qty' => $qty, 'sales' => round($sales, 2)];
        })->values()->sortByDesc('sales')->take(5)->all();

        $productIds = $products->pluck('id');
        $ratings = ProductRating::whereIn('product_id', $productIds)->get();
        $ratingStats = [
            'count' => $ratings->count(),
            'avg' => $ratings->count() ? round((float) $ratings->avg('stars'), 2) : 0.0,
        ];
        $ratingsByProduct = $ratings->groupBy('product_id')->map(function ($group) {
            return [
                'avg' => round((float) $group->avg('stars'), 2),
                'count' => $group->count(),
            ];
        });

        return view('provider.dashboard', compact('products', 'totals', 'topProducts', 'ratingStats', 'ratingsByProduct'));
    }

    /**
     * Muestra el formulario para crear un nuevo producto.
     */
    public function create()
    {
        return view('provider.product.create');
    }

    /**
     * Almacena un nuevo producto en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:comida,limpieza,electronica,herramientas,otros',
            'price' => 'required|numeric|min:0.01',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $providerId = Auth::user()->provider->id;

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'products/'.$providerId.'-'.uniqid().'.'.$file->getClientOriginalExtension();
            $stored = $file->storeAs('', $filename, 'public');
            $imagePath = Storage::url($stored);
        }

        Product::create([
            'provider_id' => $providerId,
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'price' => $request->price,
            'image_url' => $imagePath,
            'is_available' => true,
        ]);

        return redirect()->route('provider.dashboard')->with('success', '¡Producto agregado con éxito!');
    }

    /**
     * Muestra el formulario para editar un producto.
     */
    public function edit(Product $product)
    {
        // Seguridad: Asegura que el proveedor solo pueda editar sus propios productos.
        if (Auth::user()->provider->id !== $product->provider_id) {
            return redirect()->route('provider.dashboard')->with('error', 'No tienes permiso para editar este producto.');
        }

        return view('provider.product.edit', compact('product'));
    }

    /**
     * Actualiza un producto específico del proveedor.
     */
    public function update(Request $request, Product $product)
    {
        // 1. Verificar autorización
        if (Auth::user()->provider->id !== $product->provider_id) {
            return redirect()->route('provider.dashboard')->with('error', 'Acceso denegado para actualizar este producto.');
        }

        // 2. Validación de campos
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:comida,limpieza,electronica,herramientas,otros',
            'price' => 'required|numeric|min:0.01',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'is_available' => 'sometimes|boolean',
        ]);

        // 3. Actualizar
        $updateData = [
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'price' => $request->price,
            'is_available' => $request->boolean('is_available'),
        ];
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = $product->provider_id.'-'.uniqid().'.'.$file->getClientOriginalExtension();
            $path = Storage::disk('public')->putFileAs('products', $file, $filename);
            $updateData['image_url'] = Storage::url($path);
        }
        $product->update($updateData);

        return redirect()->route('provider.dashboard')->with('success', 'Producto actualizado con éxito.');
    }

    /**
     * Elimina un producto.
     */
    public function destroy(Product $product)
    {
        // 1. Verificar autorización
        if (Auth::user()->provider->id !== $product->provider_id) {
            return redirect()->route('provider.dashboard')->with('error', 'Acceso denegado para eliminar este producto.');
        }

        $product->delete();

        return redirect()->route('provider.dashboard')->with('success', 'Producto eliminado con éxito.');
    }

    public function uploadLogo(Request $request)
    {
        $user = Auth::user();
        $provider = $user->provider;
        if (!$provider) {
            abort(403);
        }

        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $file = $request->file('logo');
        $filename = 'provider_logos/'.$provider->id.'-'.uniqid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('', $filename, 'public');
        $provider->logo_url = Storage::url($path);
        $provider->save();

        return redirect()->route('provider.dashboard')->with('success', 'Logo actualizado correctamente.');
    }

    /**
     * Reporte de ventas del proveedor con filtro por rango de fechas.
     */
    public function reports(Request $request)
    {
        $provider = Auth::user()->provider;
        if (!$provider) {
            abort(403);
        }

        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $from = $request->input('from');
        $to = $request->input('to');

        if (!$from) {
            $from = now()->startOfMonth()->toDateString();
        }
        if (!$to) {
            $to = now()->endOfDay()->toDateString();
        }
        $fromDate = \Carbon\Carbon::parse($from)->startOfDay();
        $toDate = \Carbon\Carbon::parse($to)->endOfDay();
        if ($toDate->lt($fromDate)) {
            $toDate = $fromDate->copy()->endOfDay();
        }
        // Limitar rango máximo a 365 días para rendimiento
        if ($toDate->diffInDays($fromDate) > 365) {
            $fromDate = $toDate->copy()->subDays(365)->startOfDay();
        }

        $orders = Order::where('provider_id', $provider->id)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        $totals = [
            'orders_count' => $orders->count(),
            'subtotal' => (float) $orders->sum('subtotal'),
            'commission_total' => (float) $orders->sum('commission'),
            'total' => (float) $orders->sum('total'),
            'paid_subtotal' => (float) $orders->where('status', 'paid')->sum('subtotal'),
            'paid_total' => (float) $orders->where('status', 'paid')->sum('total'),
            'paid_commission' => (float) $orders->where('status', 'paid')->sum('commission'),
            'estimated_income_paid' => (float) $orders->where('status', 'paid')->sum('total') - (float) $orders->where('status', 'paid')->sum('commission'),
        ];

        $statusCounts = [
            'pending' => $orders->where('status', 'pending')->count(),
            'paid' => $orders->where('status', 'paid')->count(),
            'cancelled' => $orders->where('status', 'cancelled')->count(),
            'refund_requested' => $orders->where('status', 'refund_requested')->count(),
        ];

        $orderIds = $orders->pluck('id');
        $items = OrderItem::whereIn('order_id', $orderIds)->get();
        $productSummary = $items->groupBy('product_id')->map(function ($group) {
            $name = optional($group->first())->name;
            $qty = (int) $group->sum('qty');
            $sales = 0.0;
            foreach ($group as $it) {
                $sales += ($it->price * $it->qty);
            }
            return [
                'name' => $name,
                'qty' => $qty,
                'sales' => round($sales, 2),
            ];
        })->values()->sortByDesc('sales')->all();

        return view('provider.reports', compact('totals', 'statusCounts', 'productSummary', 'from', 'to'));
    }

    public function updateLocation(Request $request)
    {
        $provider = Auth::user()->provider;
        if (!$provider) {
            abort(403);
        }
        $address = trim((string) ($request->input('location') ?: $provider->location));
        $geo = new \App\Services\GeolocationService();
        $coords = $geo->geocode($address);
        $provider->lat = $coords['lat'] ?? null;
        $provider->lng = $coords['lng'] ?? null;
        $provider->save();
        return redirect()->route('provider.dashboard')->with('success', 'Ubicación actualizada');
    }
}
