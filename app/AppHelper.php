<?php // Code within app\Helpers\Helper.php

namespace App;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class AppHelper
{
  public static function formatRupiah($angka)
  {
    $hasil = "Rp " . number_format($angka, 2, ',', '.');
    return $hasil;
  }

  public static function geocodeAddressTest($address) {
    $encodedAddress = urlencode($address);

    $response = Http::get("https://nominatim.openstreetmap.org/search?format=json&q={$encodedAddress}&format=json&addressdetails=1&limit=1");

    if ($response->successful()) {
        $data = $response->json();
        if (count($data) > 0) {
            $latitude = $data[0]['lat'];
            $longitude = $data[0]['lon'];

            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        }
    }

    return null;
  }

}
