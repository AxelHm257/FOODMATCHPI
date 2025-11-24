<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Provider;
use App\Models\ProductRating;
use App\Models\ProviderRating;

class RatingController extends Controller
{
    public function rateProduct(Request $request, Product $product)
    {
        $request->validate([
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $existing = ProductRating::where('product_id', $product->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existing) {
            return back()->with('error', 'Ya calificaste este producto.');
        } else {
            ProductRating::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'stars' => (int) $request->input('stars'),
                'comment' => $request->input('comment'),
            ]);
        }

        return back()->with('success', 'Calificación del producto guardada.');
    }

    public function rateProvider(Request $request, Provider $provider)
    {
        $request->validate([
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $existing = ProviderRating::where('provider_id', $provider->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existing) {
            return back()->with('error', 'Ya calificaste a este proveedor.');
        } else {
            ProviderRating::create([
                'provider_id' => $provider->id,
                'user_id' => Auth::id(),
                'stars' => (int) $request->input('stars'),
                'comment' => $request->input('comment'),
            ]);
        }

        return back()->with('success', 'Calificación del proveedor guardada.');
    }
}
