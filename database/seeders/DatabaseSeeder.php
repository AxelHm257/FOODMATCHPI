<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Provider;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@foodmatch.local'],
            ['name' => 'Administrador', 'password' => Hash::make('admin123'), 'role' => 'admin']
        );
        $providerUser = User::firstOrCreate(
            ['email' => 'provider@foodmatch.local'],
            ['name' => 'Proveedor Demo', 'password' => Hash::make('provider123'), 'role' => 'provider']
        );
        $customer = User::firstOrCreate(
            ['email' => 'cliente@foodmatch.local'],
            ['name' => 'Cliente Demo', 'password' => Hash::make('cliente123'), 'role' => 'customer']
        );

        $provider = Provider::firstOrCreate(
            ['user_id' => $providerUser->id],
            [
                'name' => 'FoodMatch Proveedor',
                'contact' => 'contacto@foodmatch.local',
                'logo_url' => null,
                'location' => 'CDMX',
                'lat' => null,
                'lng' => null,
                'description' => 'Proveedor de ejemplo para desarrollo.'
            ]
        );

        Product::updateOrCreate(
            ['provider_id' => $provider->id, 'name' => 'Sudadera "Cosmos" Algodón Orgánico'],
            [
                'description' => 'Edición demo',
                'category' => 'garnacha',
                'price' => 899.00,
                'image_url' => null,
                'is_available' => true,
            ]
        );
        Product::updateOrCreate(
            ['provider_id' => $provider->id, 'name' => 'Gafas de Sol "Eco-Trail" con Armazón de Bambú'],
            [
                'description' => 'Edición demo',
                'category' => 'nutritiva',
                'price' => 450.00,
                'image_url' => null,
                'is_available' => true,
            ]
        );

        $providersDemo = [
            ['email' => 'provider1@foodmatch.local', 'name' => 'Cafetería Aurora', 'location' => 'CDMX'],
            ['email' => 'provider2@foodmatch.local', 'name' => 'Mercado Luna', 'location' => 'Guadalajara'],
            ['email' => 'provider3@foodmatch.local', 'name' => 'Eco Delicias', 'location' => 'Monterrey'],
            ['email' => 'provider4@foodmatch.local', 'name' => 'Bosque Fresco', 'location' => 'Puebla'],
            ['email' => 'provider5@foodmatch.local', 'name' => 'La Ruta Verde', 'location' => 'Querétaro'],
        ];
        foreach ($providersDemo as $p) {
            $u = User::firstOrCreate(
                ['email' => $p['email']],
                ['name' => $p['name'].' (Usuario)', 'password' => Hash::make('provider123'), 'role' => 'provider']
            );
            Provider::firstOrCreate(
                ['user_id' => $u->id],
                [
                    'name' => $p['name'],
                    'contact' => strtolower(str_replace(' ', '', $p['name'])).'@foodmatch.local',
                    'logo_url' => null,
                    'location' => $p['location'],
                    'lat' => null,
                    'lng' => null,
                    'description' => 'Negocio de prueba generado automáticamente.'
                ]
            );
        }

        $allProviders = Provider::all();
        $foodCategories = ['nutritiva','garnacha','mixto','vegetariana','vegana','postre','bebida','desayuno','comida','cena','snack'];
        foreach ($allProviders as $prov) {
            $isUrban = strtolower(trim($prov->name)) === 'estilo urbano';
            $productsToCreate = $isUrban ? [
                ['name' => 'Combo Urbano Clásico', 'description' => 'Paquete de producto urbano', 'category' => 'garnacha', 'price' => 199.00],
            ] : [
                ['name' => 'Paquete Degustación', 'description' => 'Selección especial del proveedor', 'category' => 'mixto', 'price' => 149.00],
                ['name' => 'Edición Premium', 'description' => 'Producto premium del catálogo', 'category' => 'nutritiva', 'price' => 249.00],
                ['name' => 'Oferta Especial', 'description' => 'Artículo promocional del proveedor', 'category' => 'postre', 'price' => 99.00],
            ];
            foreach ($productsToCreate as $pd) {
                Product::updateOrCreate(
                    ['provider_id' => $prov->id, 'name' => $pd['name']],
                    [
                        'description' => $pd['description'],
                        'category' => $pd['category'],
                        'price' => $pd['price'],
                        'image_url' => null,
                        'is_available' => true,
                    ]
                );
            }
            $existing = Product::where('provider_id', $prov->id)->whereNull('category')->get();
            $i = 0;
            foreach ($existing as $ex) {
                $ex->category = $foodCategories[$i % count($foodCategories)];
                $ex->save();
                $i++;
            }
        }
    }
}
