<?php

namespace App\Http\Controllers;

use App\Models\Product; // << CAMBIO CLAVE
use App\Models\Provider;
use App\Models\Order;
use App\Models\ProductRating;
use App\Models\ProviderRating;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerProductController extends Controller
{
    /**
     * Muestra la lista de productos del proveedor y el formulario para crear uno.
     */
    public function index(Request $request)
    {
        $providers = Provider::with(['products' => function ($query) {
            $query->where('is_available', true);
        }])->get();

        $providersWithMenus = $providers->mapWithKeys(function ($provider) {
            return [$provider->name => $provider->products];
        });

        $view = (string) $request->query('view', 'cards');
        if (!in_array($view, ['cards', 'list', 'calendar'], true)) {
            $view = 'cards';
        }
        $currentDay = (string) $request->query('day', Carbon::now()->toDateString());
        $start = Carbon::parse($currentDay)->startOfWeek(Carbon::MONDAY);
        $weekDays = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $start->copy()->addDays($i);
            $weekDays[] = [
                'date' => $d->toDateString(),
                'label' => $d->isoFormat('dd D/MM'),
                'is_today' => $d->isSameDay(Carbon::now()),
            ];
        }

        $allMenus = collect($providersWithMenus)->flatten(1);
        $menuStats = [
            'count' => (int) $allMenus->count(),
            'avg_price' => (float) round((float) $allMenus->avg('price'), 2),
            'min_price' => (float) ($allMenus->min('price') ?? 0),
            'max_price' => (float) ($allMenus->max('price') ?? 0),
        ];

        $productIds = $allMenus->pluck('id');
        $productRatings = ProductRating::whereIn('product_id', $productIds)->get();
        $productStatsById = $productRatings->groupBy('product_id')->map(function ($group) {
            return [
                'avg' => round((float) $group->avg('stars'), 2),
                'count' => $group->count(),
            ];
        });
        $productRatedIds = ProductRating::whereIn('product_id', $productIds)->where('user_id', Auth::id())->pluck('product_id')->all();
        $productRatedByUser = [];
        foreach ($productRatedIds as $pid) { $productRatedByUser[(int)$pid] = true; }

        $providerIds = $providers->pluck('id');
        $providerRatings = ProviderRating::whereIn('provider_id', $providerIds)->get();
        $providerStatsById = $providerRatings->groupBy('provider_id')->map(function ($group) {
            return [
                'avg' => round((float) $group->avg('stars'), 2),
                'count' => $group->count(),
            ];
        });
        $providerStatsByName = $providers->mapWithKeys(function ($p) use ($providerStatsById) {
            $stats = $providerStatsById[$p->id] ?? ['avg' => 0.0, 'count' => 0];
            return [$p->name => ['id' => $p->id, 'avg' => $stats['avg'], 'count' => $stats['count']]];
        });
        $ratedIds = ProviderRating::whereIn('provider_id', $providerIds)->where('user_id', Auth::id())->pluck('provider_id')->all();
        $providerRatedByUser = [];
        foreach ($ratedIds as $rid) { $providerRatedByUser[(int)$rid] = true; }
        $deliveries = Order::where('user_id', Auth::id())->latest()->limit(5)->get();

        return view('customer.home', compact('providersWithMenus', 'view', 'currentDay', 'weekDays', 'menuStats', 'deliveries', 'productStatsById', 'providerStatsByName', 'providerRatedByUser', 'productRatedByUser'));
    }

    public function profile(Request $request, Provider $provider)
    {
        $menus = $provider->products()->where('is_available', true)->get();
        $images = $menus->pluck('image_url')->filter()->unique()->values();
        $ratings = $provider->ratings()->latest()->limit(20)->get();
        $stats = [
            'avg' => $provider->ratings()->count() ? round((float) $provider->ratings()->avg('stars'), 1) : 0.0,
            'count' => (int) $provider->ratings()->count(),
        ];
        $userHasRated = ProviderRating::where('provider_id', $provider->id)->where('user_id', Auth::id())->exists();
        return view('customer.provider_profile', compact('provider', 'menus', 'images', 'ratings', 'stats', 'userHasRated'));
    }

    /**
     * Muestra el formulario para crear un nuevo producto.
     */
    public function create()
    {
        return view('provider.product.create'); // << CAMBIO DE CARPETA DE VISTA
    }

    /**
     * Almacena un nuevo producto en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.01',
            'image_url' => 'nullable|url|max:255',
        ]);

        $provider = Auth::user()->provider;

        Product::create([ // << CAMBIO CLAVE
            'provider_id' => $provider->id,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'image_url' => $request->image_url,
            'is_available' => true, // Por defecto, disponible al crear
        ]);

        return redirect()->route('provider.dashboard')->with('success', '¡Producto agregado con éxito!');
    }

    /**
     * Muestra el formulario para editar un producto.
     */
    public function edit(Product $product) // << CAMBIO CLAVE
    {
        if (Auth::user()->provider->id !== $product->provider_id) {
            return redirect()->route('provider.dashboard')->with('error', 'No tienes permiso para editar este producto.');
        }

        return view('provider.product.edit', compact('product')); // << CAMBIO DE CARPETA DE VISTA
    }

    /**
     * Actualiza el producto en la base de datos.
     */
    public function update(Request $request, Product $product) // << CAMBIO CLAVE
    {
        if (Auth::user()->provider->id !== $product->provider_id) {
            return redirect()->route('provider.dashboard')->with('error', 'Acceso denegado.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.01',
            'image_url' => 'nullable|url|max:255',
            'is_available' => 'sometimes|boolean',
        ]);

        $product->update([ // << CAMBIO CLAVE
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'image_url' => $request->image_url,
            'is_available' => $request->has('is_available'),
        ]);

        return redirect()->route('provider.dashboard')->with('success', '¡Producto actualizado con éxito!');
    }

    /**
     * Elimina el producto de la base de datos.
     */
    public function destroy(Product $product) // << CAMBIO CLAVE
    {
        if (Auth::user()->provider->id !== $product->provider_id) {
            return redirect()->route('provider.dashboard')->with('error', 'Acceso denegado.');
        }

        $product->delete();

        return redirect()->route('provider.dashboard')->with('success', 'Producto eliminado correctamente.');
    }
}
