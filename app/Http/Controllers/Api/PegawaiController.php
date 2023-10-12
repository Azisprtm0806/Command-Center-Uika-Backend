<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Simpeg_Pegawai;
use Illuminate\Http\Request;


class PegawaiController extends Controller
{
  public function jmlTenagaPengajarPerProdi(){
    try {
      $data = Simpeg_Pegawai::select('adm_lookup.lookup_id', 'adm_lookup.lookup_value', \DB::raw('COUNT(simpeg_pegawai.id) as total'))
        ->join('adm_lookup', 'adm_lookup.lookup_id', '=', 'simpeg_pegawai.division')
        ->where('adm_lookup.lookup_name', 'DIVISION')
        ->where('simpeg_pegawai.klasi_pegawai', 'PENDIDIK (DOSEN)')
        ->where('adm_lookup.lookup_id', '!=', 'AKADEMIK')
        ->where('simpeg_pegawai.status_kerja', 'AKTIF')
        ->groupBy('adm_lookup.lookup_id', 'adm_lookup.lookup_value')
        ->get();

        return new ApiResource(true, 'Jumlah Tenaga Pengajar Perprodi', $data);

    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }

  public function jmlTenagaKependidikan(){
    try {
      $data = Simpeg_Pegawai::select('adm_lookup.lookup_id', 'adm_lookup.lookup_value', \DB::raw('COUNT(simpeg_pegawai.id) as total'))
      ->join('adm_lookup', 'adm_lookup.lookup_id', '=', 'simpeg_pegawai.division')
      ->where('adm_lookup.lookup_name', 'DIVISION')
      ->where('simpeg_pegawai.klasi_pegawai', 'TENAGA KEPENDIDIKAN')
      ->where('simpeg_pegawai.status_kerja', 'AKTIF')
      ->groupBy('adm_lookup.lookup_id', 'adm_lookup.lookup_value')
      ->get();

      return new ApiResource(true, 'Jumlah Tenaga Kerja Kependidikan.', $data);
    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }
}