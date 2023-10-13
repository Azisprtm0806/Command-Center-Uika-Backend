<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Pmb_Provinsi;
use App\Models\Pmb_Registration;
use App\Models\Siak_Departemen;
use App\Models\Simpeg_Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartController extends Controller
{
    public function mhsChart(Request $request){
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
              $dataTerimaProdi = Pmb_Registration::join('pmb_registration_payment AS b', 'b.registration_no', '=', 'pmb_registration.registration_no')
                ->join('pmb_candidate AS c', 'c.registration_no', '=', 'pmb_registration.registration_no')
                ->join('siak_department AS d', 'd.code', '=', 'pmb_registration.department_code')
                ->join('siak_faculty AS e', 'e.code', '=', 'd.faculty_code')
                ->where('b.paid', 'Y')
                ->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
                ->where('pmb_registration.academic_year', '2023/2024')
                ->where('pmb_registration.semester', 'GASAL')
                ->groupBy('d.code', 'e.name', 'd.name')
                ->orderBy('e.code')
                ->orderBy('d.npm_code')
                ->select('e.name AS fakultas', 'd.name AS prodi', \DB::raw('SUM(CASE WHEN c.student_code <> "" THEN 1 ELSE 0 END) AS total'))
                ->get();

                foreach ($dataTerimaProdi as $key => $item) {
                  $dataTerimaProdi[$key]['total'] = intval($item['total']);
              }
            
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

              foreach ($dataTerimaProdi as $item) {
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
              $dataPerProvinsiDaftar = Pmb_Provinsi::join('pmb_candidate AS c', 'pmb_provinsi.id', '=', 'c.prov_code')
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

              $dataPerProvinsiDiterima = Pmb_Provinsi::join('pmb_candidate AS c', 'pmb_provinsi.id', '=', 'c.prov_code')
                ->join('pmb_registration AS a', 'c.registration_no', '=', 'a.registration_no')
                ->join('pmb_registration_payment AS b', 'a.registration_no', '=', 'b.registration_no')
                ->where('b.paid', 'Y')
                ->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
                ->where('a.academic_year', '2023/2024')
                ->where('a.semester', 'GASAL')
                ->groupBy('pmb_provinsi.id', 'pmb_provinsi.name')
                ->orderBy('pmb_provinsi.name', 'ASC')
                ->select('pmb_provinsi.name AS provinsi', \DB::raw('SUM(CASE WHEN c.student_code <> "" THEN 1 ELSE 0 END) AS total'))
                ->get();

              
              $totalDaftarMhs = [];
              $totalDiterimaMhs = [];
              $totalsJumlahMhs = [];
              $labels = [];

              foreach ($dataPerProvinsiDaftar as $item) {
                $provinsi = $item['provinsi'];
                $total = $item['total'];

                $labels[] = $provinsi;
                $totalDaftarMhs[] = $total;
              }

              foreach ($dataPerProvinsiDiterima as $item) {
                $provinsi = $item['provinsi'];
                $total = $item['total'];

                $totalDiterimaMhs[] = $total;
              }

              foreach (array_keys($totalDaftarMhs) as $provinsi) {
                $jumlah = isset($totalDiterimaMhs[$provinsi]) ? $totalDiterimaMhs[$provinsi] : 0;
                $totalsJumlahMhs[] = $totalDaftarMhs[$provinsi] + $jumlah;
            }

              $finalResponse = [
                'series' => [
                  [
                      'name' => 'Mahasiswa Daftar Per Provinsi',
                      'type' => 'column',
                      'data' => $totalDaftarMhs,
                  ],
                  [
                      'name' => 'Mahasiswa Diterima Per  Provinsi',
                      'type' => 'column',
                      'data' => $totalDiterimaMhs,
                  ],
                  [
                      'name' => 'Jumlah Mahasiswa',
                      'type' => 'line',
                      'data' => $totalsJumlahMhs,
                  ],
              ],
                
                'label' => $labels
              ];

              return new ApiResource(true, 'Chart Mahasiswa Per Provinsi', $finalResponse);
            }
          }catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
          }
    }

    public function jmlTunggakanPerProdi(){
      try {
        $data = Siak_Departemen::select('siak_department.name as prodi', \DB::raw('COUNT(siak_student.code) as jml_mhs'), \DB::raw('SUM(siak_fee_payment.nominal) as total_piutang'))
          ->join('siak_student', 'siak_department.code', '=', 'siak_student.department_code')
          ->join('siak_fee_payment', 'siak_fee_payment.student_code', '=', 'siak_student.code')
          ->where('siak_fee_payment.academic_year', '2023/2024')
          ->where('siak_fee_payment.semester', 'GASAL')
          ->where('siak_fee_payment.paid', 'N')
          ->groupBy('siak_department.code', 'siak_department.name')
          ->get();

          $dataTunggakan = [];
          $jml_mhs = [];
          $labelProdi = [];

          foreach($data as $item){
            $prodi = $item['prodi'];
            $tunggakan = $item['total_piutang'];
            $jmlMhs = $item['jml_mhs'];

            $labelProdi[] = $prodi;
            $dataTunggakan[] = $tunggakan;
            $jml_mhs[] = $jmlMhs;
          }

          $finalResponse = [
            'series' => [
              [
                  'name' => 'Jumlah Tunggakan',
                  'type' => 'area',
                  'data' => $dataTunggakan,
              ],
              [
                  'name' => 'Jumlah Mahasiswa',
                  'type' => 'line',
                  'data' => $jml_mhs,
              ],
              
          ],
            
            'label' => $labelProdi
          ];

  
          return new ApiResource(true, 'Jumlah Tunggakan Mahasiswa Per Prodi', $finalResponse);
    
      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

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


          $dataTotalPengajar = [];
          $labelProdi = [];

          foreach($data as $item){
            $prodi = $item['lookup_value'];
            $total = $item['total'];
  
            $labelProdi[] = $prodi;
            $dataTotalPengajar[] = $total;
          }

          $finalResponse = [
            'series' => [
              [
                  'name' => 'Total Data Pengajar',
                  'type' => 'column',
                  'data' => $dataTotalPengajar,
              ],
              
          ],
            
            'label' => $labelProdi
          ];

  
          return new ApiResource(true, 'Jumlah Tenaga Pengajar Perprodi', $finalResponse);
  
      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

}