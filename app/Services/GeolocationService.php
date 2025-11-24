<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeolocationService
{
    public function geocode(string $address): array
    {
        $provider = (string) config('services.geo.provider');
        if ($provider === 'mapbox' && config('services.geo.mapbox.token')) {
            $token = (string) config('services.geo.mapbox.token');
            $res = Http::get('https://api.mapbox.com/geocoding/v5/mapbox.places/'.urlencode($address).'.json', [
                'access_token' => $token,
                'limit' => 1,
            ])->json();
            $center = $res['features'][0]['center'] ?? null;
            if ($center) {
                return ['lat' => (float) $center[1], 'lng' => (float) $center[0]];
            }
        }
        if ($provider === 'google' && config('services.geo.google.key')) {
            $key = (string) config('services.geo.google.key');
            $res = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $key,
            ])->json();
            $loc = $res['results'][0]['geometry']['location'] ?? null;
            if ($loc) {
                return ['lat' => (float) $loc['lat'], 'lng' => (float) $loc['lng']];
            }
        }
        $res = Http::get('https://nominatim.openstreetmap.org/search', [
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
        ])->json();
        $first = $res[0] ?? null;
        if ($first) {
            return ['lat' => (float) ($first['lat'] ?? 0), 'lng' => (float) ($first['lon'] ?? 0)];
        }
        return ['lat' => 0, 'lng' => 0];
    }
}

