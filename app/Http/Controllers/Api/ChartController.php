<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Pmb_Provinsi;
use App\Models\Pmb_Registration;
use Illuminate\Http\Request;


class ChartController extends Controller
{
    public function mhsDaftar(Request $request){
        $key = $request->key;

          try {
            $dataDaftar = Pmb_Registration::join('pmb_registration_payment AS b', 'b.registration_no', '=', 'pmb_registration.registration_no')
              ->join('pmb_candidate AS c', 'c.registration_no', '=', 'pmb_registration.registration_no')
              ->join('siak_department AS d', 'd.code', '=', 'pmb_registration.department_code')
              ->join('siak_faculty AS e', 'e.code', '=', 'd.faculty_code')
              ->where('b.paid', 'Y')
              ->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
              ->where('pmb_registration.academic_year', '2023/2024')
              ->where('pmb_registration.semester', 'GASAL')
              ->groupBy('e.name', 'd.name', 'd.code')
              ->orderBy('e.code')
              ->orderBy('d.npm_code')
              ->select('e.name AS fakultas', 'd.name AS prodi', \DB::raw('COUNT(pmb_registration.registration_no) AS total'))
              ->get();

              $dataTerima = Pmb_Registration::join('pmb_registration_payment AS b', 'b.registration_no', '=', 'pmb_registration.registration_no')
              ->join('pmb_candidate AS c', 'c.registration_no', '=', 'pmb_registration.registration_no')
              ->join('siak_department AS d', 'd.code', '=', 'pmb_registration.department_code')
              ->join('siak_faculty AS e', 'e.code', '=', 'd.faculty_code')
              ->where('b.paid', 'Y')
              ->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
              ->where('pmb_registration.academic_year', '2023/2024')
              ->where('pmb_registration.semester', 'GASAL')
              ->where('c.student_code', '<>', '')
              ->groupBy('d.code', 'e.name', 'd.name')
              ->orderBy('e.code')
              ->orderBy('d.npm_code')
              ->select('e.name AS fakultas', 'd.name AS prodi', \DB::raw('COUNT(pmb_registration.registration_no) AS total'))
              ->get();

            if($key == 'fakultas'){
              $totalsDaftar = [];
              foreach ($dataDaftar as $item) {
                $fakultas = $item['fakultas'];
                $total = $item['total'];
    
                if (!isset($totalsDaftar[$fakultas])) {
                    $totalsDaftar[$fakultas] = $total;
                } else {
                    $totalsDaftar[$fakultas] += $total;
                }
              }
    
              $totalsTerima = [];
              foreach ($dataTerima as $item) {
                $fakultas = $item['fakultas'];
                $total = $item['total'];
    
                if (!isset($totalsTerima[$fakultas])) {
                    $totalsTerima[$fakultas] = $total;
                } else {
                    $totalsTerima[$fakultas] += $total;
                }
              }
    
              $TotalDaftar = array_values($totalsDaftar);
              $TotalTerima = array_values($totalsTerima);
              $totalsJumlah = [];
              $totalsFakultas = array_keys($totalsDaftar);
    
              foreach (array_keys($totalsDaftar) as $fakultas) {
                  $jumlah = isset($totalsTerima[$fakultas]) ? $totalsTerima[$fakultas] : 0;
                  $totalsJumlah[] = $totalsDaftar[$fakultas] + $jumlah;
              }
    
              $finalResponse = [
                'series' => [
                    [
                        'name' => 'Mahasiswa Daftar',
                        'type' => 'column',
                        'data' => $TotalDaftar, 
                    ],
                    [
                      'name' => 'Mahasiswa Diterima',
                      'type' => 'column',
                      'data' => $TotalTerima
                    ],
                    [
                      'name' => 'Jumlah Mahasiswa',
                      'type' => 'line',
                      'data' => $totalsJumlah
                    ]
                    ],
                'label' => $totalsFakultas
              ];
    
              return new ApiResource(true, 'Chart Mahasiswa Per Faultas', $finalResponse);
            } else if($key == 'prodi'){
              $totalsMahasiswaDaftar = [];
              $totalsMahasiswaDiterima = [];
              $totalsJumlah = [];

              $labels = [];

              foreach ($dataDaftar as $item) {
                  $prodi = $item['prodi'];
                  $total = $item['total'];

                  $labels[] = $prodi;

                  $totalsMahasiswaDaftar[] = $total;
              }

              foreach ($dataTerima as $item) {
                  $total = $item['total'];

                  $totalsMahasiswaDiterima[] = $total;
              }

              foreach (array_keys($totalsMahasiswaDaftar) as $prodi) {
                  $jumlah = isset($totalsMahasiswaDiterima[$prodi]) ? $totalsMahasiswaDiterima[$prodi] : 0;
                  $totalsJumlah[] = $totalsMahasiswaDaftar[$prodi] + $jumlah;
              }

              $finalResponse = [
                  'series' => [
                      [
                          'name' => 'Mahasiswa Daftar',
                          'type' => 'column',
                          'data' => $totalsMahasiswaDaftar,
                      ],
                      [
                          'name' => 'Mahasiswa Diterima',
                          'type' => 'column',
                          'data' => $totalsMahasiswaDiterima,
                      ],
                      [
                          'name' => 'Jumlah Mahasiswa',
                          'type' => 'line',
                          'data' => $totalsJumlah,
                      ],
                  ],
                  'label' => $labels
              ];

              return new ApiResource(true, 'Chart Mahasiswa Per Prodi', $finalResponse);
            } else if($key == 'provinsi'){
              
              $dataPerProvinsi = Pmb_Provinsi::join('pmb_candidate AS c', 'pmb_provinsi.id', '=', 'c.prov_code')
              ->join('pmb_registration AS a', 'c.registration_no', '=', 'a.registration_no')
              ->join('pmb_registration_payment AS b', 'a.registration_no', '=', 'b.registration_no')
              ->where('b.paid', 'Y')
              ->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
              ->where('a.academic_year', '2023/2024')
              ->where('a.semester', 'GASAL')
              ->groupBy('pmb_provinsi.id', 'pmb_provinsi.name')
              ->orderBy('pmb_provinsi.name', 'ASC')
              ->select('pmb_provinsi.name AS provinsi', \DB::raw('COUNT(a.registration_no) AS total'))
              ->get();

              
              $totalMahasiswa = [];
              $labels = [];

              foreach ($dataPerProvinsi as $item) {
                $provinsi = $item['provinsi'];
                $total = $item['total'];

                $labels[] = $provinsi;
                $totalMahasiswa[] = $total;
              }

              $finalResponse = [
                'series' => [
                  'name' => 'Total Per Provinsi',
                  'type' => 'column',
                  'data' => $totalMahasiswa,
                ],
                'label' => $labels
              ];

              return new ApiResource(true, 'Chart Mahasiswa Per Provinsi', $finalResponse);
            }
          }catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
          }
    }
}