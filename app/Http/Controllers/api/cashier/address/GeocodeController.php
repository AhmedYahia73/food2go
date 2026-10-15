<?php

namespace App\Http\Controllers\api\cashier\address;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeocodeController extends Controller
{
    /**
     * Get human-readable address from coordinates (Reverse Geocode).
     * Supports Google Maps API if configured, otherwise falls back to OpenStreetMap Nominatim.
     */
    public function getBranchAddress(Request $request)
    {
        $user = auth()->user();
        $branch = $user?->branch;

        $lat = $request->input('latitude', $request->input('lat', $branch?->latitude));
        $lng = $request->input('longitude', $request->input('lng', $request->input('lon', $branch?->longitude)));
        $locale = $request->input('locale', 'ar');

        if (empty($lat) || empty($lng)) {
            $existingAddress = $branch?->address;
            if (!empty($existingAddress) && !preg_match('/^-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?$/', trim($existingAddress))) {
                return response()->json([
                    'status' => true,
                    'address' => $existingAddress,
                    'source' => 'branch_record'
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Coordinates (latitude, longitude) are required.',
            ], 400);
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        // Cache result for 30 days to avoid repeated requests and speed up response
        $cacheKey = "geocode_address_{$lat}_{$lng}_{$locale}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return response()->json([
                'status' => true,
                'address' => $cached,
                'cached' => true,
            ]);
        }

        $address = null;

        // 1. Try Google Maps Geocoding if API key is present in env/config
        $googleKey = config('services.google.maps_api_key') ?? env('GOOGLE_MAPS_API_KEY');
        if (!empty($googleKey)) {
            try {
                $response = Http::timeout(4)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => "{$lat},{$lng}",
                    'language' => $locale,
                    'key' => $googleKey,
                ]);

                if ($response->successful() && !empty($response->json('results.0.formatted_address'))) {
                    $address = $response->json('results.0.formatted_address');
                }
            } catch (\Exception $e) {
                Log::warning('Google Geocode error: ' . $e->getMessage());
            }
        }

        // 2. Try OpenStreetMap (Nominatim) if Google is not configured or failed
        if (empty($address)) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Food2Go-POS/1.0 (contact@food2go.online)',
                    'Accept-Language' => $locale,
                ])->timeout(5)->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'json',
                    'lat' => $lat,
                    'lon' => $lng,
                    'accept-language' => $locale,
                    'addressdetails' => 1,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $components = $data['address'] ?? [];

                    $road = $components['road'] ?? $components['pedestrian'] ?? $components['street'] ?? null;
                    $area = $components['neighbourhood'] ?? $components['suburb'] ?? $components['district'] ?? $components['quarter'] ?? null;
                    $city = $components['city'] ?? $components['town'] ?? $components['village'] ?? $components['municipality'] ?? $components['state'] ?? null;

                    $parts = array_filter([$road, $area, $city]);
                    if (!empty($parts)) {
                        $address = implode('، ', $parts);
                    } else {
                        $address = $data['display_name'] ?? null;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Nominatim Geocode error: ' . $e->getMessage());
            }
        }

        // 3. Fallback to BigDataCloud
        if (empty($address)) {
            try {
                $response = Http::timeout(4)->get('https://api.bigdatacloud.net/data/reverse-geocode-client', [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'localityLanguage' => $locale,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $address = $data['display_name'] ?? ($data['locality'] ?? null);
                }
            } catch (\Exception $e) {
                Log::warning('BigDataCloud Geocode error: ' . $e->getMessage());
            }
        }

        if (!empty($address)) {
            Cache::put($cacheKey, $address, now()->addDays(30));

            return response()->json([
                'status' => true,
                'address' => $address,
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Unable to resolve address from coordinates.',
        ], 500);
    }
}
