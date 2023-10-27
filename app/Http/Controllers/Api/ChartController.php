<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Bkpl_Event;
use App\Models\BkplPrograms;
use App\Models\Pmb_Candidate;
use App\Models\Pmb_Provinsi;
use App\Models\Pmb_Registration;
use App\Models\Siak_Departemen;
use App\Models\Siak_Student;
use App\Models\Siak_Student_Snapshot;
use App\Models\Simpeg_Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartController extends Controller
{
    public function mhsChart(Request $request){
      $key = $request->key;
      $tahunAkademik = $request->tahun_akademik;
      $semester = strtoupper($request->semester);

      if(empty($key) || $key == null){
        return response()->json(['message' => 'key param required.']);
      }
      if(empty($tahunAkademik) || $tahunAkademik == null){
        return response()->json(['message' => 'tahun_akademik param required.']);
      }
      if(empty($semester) || $semester == null){
        return response()->json(['message' => 'semester param required.']);
      }

        try {
          $dataDaftar = Pmb_Registration::join('pmb_registration_payment AS b', 'b.registration_no', '=', 'pmb_registration.registration_no')
            ->join('pmb_candidate AS c', 'c.registration_no', '=', 'pmb_registration.registration_no')
            ->join('siak_department AS d', 'd.code', '=', 'pmb_registration.department_code')
            ->join('siak_faculty AS e', 'e.code', '=', 'd.faculty_code')
            ->where('b.paid', 'Y')
            ->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
            ->where('pmb_registration.academic_year', $tahunAkademik)
            ->where('pmb_registration.semester', $semester)
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
            ->where('pmb_registration.academic_year', $tahunAkademik)
            ->where('pmb_registration.semester', $semester)
            ->where('c.student_code', '<>', '')
            ->groupBy('d.code', 'e.name', 'd.name')
            ->orderBy('e.code')
            ->orderBy('d.npm_code')
            ->select('e.name AS fakultas', 'd.name AS prodi', \DB::raw('COUNT(pmb_registration.registration_no) AS total'))
            ->get();

            $dataPeminat = DB::table('pmb_registration as a')
            ->join('pmb_registration_payment as b', 'b.registration_no', '=', 'a.registration_no')
            ->join('pmb_candidate as c', 'c.registration_no', '=', 'a.registration_no')
            ->join('siak_department as d', 'd.code', '=', 'a.department_code')
            ->join('siak_faculty as e', 'e.code', '=', 'd.faculty_code')
            ->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
            ->where('a.academic_year', $tahunAkademik)
            ->where('a.semester', $semester)
            ->groupBy('e.name', 'd.name') 
            ->select('e.name as fakultas', 'd.name as prodi', DB::raw('COUNT(a.registration_no) as total'))
            ->orderBy('e.code')
            ->orderBy('d.npm_code', 'ASC')
            ->get();

          if($key == 'fakultas' && !empty($tahunAkademik) && !empty($semester)){
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

            $totalsPeminat = [];
            foreach ($dataPeminat as $item) {
              $fakultas = $item->fakultas;
              $total = $item->total;
  
              if (!isset($totalsPeminat[$fakultas])) {
                  $totalsPeminat[$fakultas] = $total;
              } else {
                  $totalsPeminat[$fakultas] += $total;
              }
            }
  
            $TotalDaftar = array_values($totalsDaftar);
            $TotalTerima = array_values($totalsTerima);
            $TotalPeminat = array_values($totalsPeminat);
            $totalsFakultas = array_keys($totalsDaftar);
  
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
                      'name' => 'Mahasiswa Peminat',
                      'type' => 'column',
                      'data' => $TotalPeminat
                    ]
                  ],
              'label' => $totalsFakultas
            ];

            return new ApiResource(true, 'Chart Mahasiswa Per Faultas', $finalResponse);
          } else if($key == 'prodi' && !empty($tahunAkademik) && !empty($semester)){
            $dataTerimaProdi = Pmb_Registration::join('pmb_registration_payment AS b', 'b.registration_no', '=', 'pmb_registration.registration_no')
              ->join('pmb_candidate AS c', 'c.registration_no', '=', 'pmb_registration.registration_no')
              ->join('siak_department AS d', 'd.code', '=', 'pmb_registration.department_code')
              ->join('siak_faculty AS e', 'e.code', '=', 'd.faculty_code')
              ->where('b.paid', 'Y')
              ->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
              ->where('pmb_registration.academic_year', $tahunAkademik)
              ->where('pmb_registration.semester', $semester)
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

            $totalsPeminat = [];
            foreach ($dataPeminat as $item) {
              $prodi = $item->prodi;
              $total = $item->total;
  
              if (!isset($totalsPeminat[$prodi])) {
                  $totalsPeminat[$prodi] = $total;
              } else {
                  $totalsPeminat[$prodi] += $total;
              }
            }

            $TotalPeminat = array_values($totalsPeminat);

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
                        'name' => 'Mahasiswa Peminat',
                        'type' => 'column',
                        'data' => $TotalPeminat,
                    ],
                ],
                'label' => $labels
            ];

            return new ApiResource(true, 'Chart Mahasiswa Per Prodi', $finalResponse);
          } else if($key == 'provinsi' && !empty($tahunAkademik) && !empty($semester)){
            $dataPerProvinsiDaftar = Pmb_Provinsi::join('pmb_candidate AS c', 'pmb_provinsi.id', '=', 'c.prov_code')
              ->join('pmb_registration AS a', 'c.registration_no', '=', 'a.registration_no')
              ->join('pmb_registration_payment AS b', 'a.registration_no', '=', 'b.registration_no')
              ->where('b.paid', 'Y')
              ->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
              ->where('a.academic_year', $tahunAkademik)
              ->where('a.semester', $semester)
              ->groupBy('pmb_provinsi.id', 'pmb_provinsi.name')
              ->orderBy('pmb_provinsi.name', 'ASC')
              ->select('pmb_provinsi.name AS provinsi', \DB::raw('COUNT(a.registration_no) AS total'))
              ->get();

            $dataPerProvinsiDiterima = Pmb_Provinsi::join('pmb_candidate AS c', 'pmb_provinsi.id', '=', 'c.prov_code')
              ->join('pmb_registration AS a', 'c.registration_no', '=', 'a.registration_no')
              ->join('pmb_registration_payment AS b', 'a.registration_no', '=', 'b.registration_no')
              ->where('b.paid', 'Y')
              ->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
              ->where('a.academic_year', $tahunAkademik)
              ->where('a.semester', $semester)
              ->groupBy('pmb_provinsi.id', 'pmb_provinsi.name')
              ->orderBy('pmb_provinsi.name', 'ASC')
              ->select('pmb_provinsi.name AS provinsi', \DB::raw('SUM(CASE WHEN c.student_code <> "" THEN 1 ELSE 0 END) AS total'))
              ->get();

            
            $totalDaftarMhs = [];
            $totalDiterimaMhs = [];
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

            $totalDiterimaMhs = array_map('intval', $totalDiterimaMhs);

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
                  'name' => 'Mahasiswa Peminat',
                  'type' => 'column',
                  'data' => [
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0
                  ]
                ],
            ],
              
              'label' => $labels
            ];

            return new ApiResource(true, 'Chart Mahasiswa Per Provinsi', $finalResponse);
          } else {
            return response()->json(['message' => 'value key param not allowed. must be [prodi, fakultas, provinsi]' ]);
          }
        }catch (\Exception $e) {
          return response()->json(['error' => $e->getMessage()], 500);
        }
    }
  
    public function jmlTunggakanPerProdi(Request $request){
      $key = $request->key;
      $tahun = $request->tahun_akademik;
      $semester = strtoupper($request->semester);

      if(empty($key) || $key == null){
        return response()->json(['message' => 'key param required.']);
      }
      if(empty($tahun) || $tahun == null){
        return response()->json(['message' => 'tahun_akademik param required.']);
      }
      if(empty($semester) || $semester == null){
        return response()->json(['message' => 'semester param required.']);
      }

      if($key === 'all' && !empty($tahun) && !empty($semester)){
          $allTunggakan = Siak_Departemen::select('siak_department.name as prodi', \DB::raw('COUNT(siak_student.code) as jml_mhs'),     \DB::raw('SUM(siak_fee_payment.nominal) as total_piutang'))
          ->join('siak_student', 'siak_department.code', '=', 'siak_student.department_code')
          ->join('siak_fee_payment', 'siak_fee_payment.student_code', '=', 'siak_student.code')
          ->where('siak_fee_payment.academic_year', $tahun)
          ->where('siak_fee_payment.semester', $semester)
          ->where('siak_fee_payment.paid', 'N')
          ->groupBy('siak_department.code', 'siak_department.name')
          ->get();

          $dataTunggakan = [];
          $jml_mhs = [];
          $labelProdi = [];

          foreach ($allTunggakan as $item) {
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
                      'name' => 'Jumlah Tunggakan Keseluruhan',
                      'type' => 'area',
                      'data' => $dataTunggakan,
                  ],
                  [
                      'name' => 'Jumlah Mahasiswa',
                      'type' => 'line',
                      'data' => $jml_mhs,
                  ],
              ],

              'label' => $labelProdi,
          ];

          return new ApiResource(true, 'Jumlah Keseluruhan Tunggakan Mahasiswa Per Prodi', $finalResponse);
      } else if($key === 'spp' && !empty($tahun) && !empty($semester)){
          $tunggakanSpp = Siak_Departemen::select('siak_department.name AS prodi', DB::raw('COUNT(siak_student.code) AS jml_mhs'), DB::raw('SUM(siak_fee_payment.nominal) AS total_piutang'))
            ->join('siak_student', 'siak_student.department_code', '=', 'siak_department.code')
            ->join('siak_fee_payment', 'siak_fee_payment.student_code', '=', 'siak_student.code')
            ->where('siak_fee_payment.academic_year', $tahun)
            ->where('siak_fee_payment.semester', $semester)
            ->where('siak_fee_payment.paid', 'N')
            ->where('siak_fee_payment.fee_item', '1020')
            ->groupBy('siak_department.code', 'siak_department.name') // Tambahkan siak_department.name di sini
            ->get();
          
            $dataTunggakanSpp = [];
            $jml_mhs_spp = [];
            $labelProdiSpp = [];
    
            foreach ($tunggakanSpp as $item) {
              $prodi = $item['prodi'];
              $tunggakan = $item['total_piutang'];
              $jmlMhs = $item['jml_mhs'];
    
              $labelProdiSpp[] = $prodi;
              $dataTunggakanSpp[] = $tunggakan;
              $jml_mhs_spp[] = $jmlMhs;
            }
    
            $finalResponse = [
                'series' => [
                    [
                        'name' => 'Jumlah Tunggakan SPP',
                        'type' => 'area',
                        'data' => $dataTunggakanSpp,
                    ],
                    [
                        'name' => 'Jumlah Mahasiswa',
                        'type' => 'line',
                        'data' => $jml_mhs_spp,
                    ],
                ],
    
                'label' => $labelProdiSpp,
            ];
    
            return new ApiResource(true, 'Jumlah Tunggakan Mahasiswa (SPP) PerProdi', $finalResponse);
      } else if($key === 'sksujian' && !empty($tahun) && !empty($semester)){
          $sksujianTunggakan = Siak_Departemen::select('siak_department.name AS prodi', DB::raw('COUNT(siak_student.code) AS jml_mhs'), DB::raw('SUM(siak_fee_payment.nominal) AS total_piutang'))
            ->join('siak_student', 'siak_student.department_code', '=', 'siak_department.code')
            ->join('siak_fee_payment', 'siak_fee_payment.student_code', '=', 'siak_student.code')
            ->where('siak_fee_payment.academic_year', $tahun)
            ->where('siak_fee_payment.semester', $semester)
            ->where('siak_fee_payment.paid', 'N')
            ->whereIn('siak_fee_payment.fee_item', ['1200', '1210'])
            ->groupBy('siak_department.code', 'siak_department.name')
            ->get();

            $dataTunggakan = [];
            $jml_mhs_ = [];
            $labelProdi = [];
    
            foreach ($sksujianTunggakan as $item) {
              $prodi = $item['prodi'];
              $tunggakan = $item['total_piutang'];
              $jmlMhs = $item['jml_mhs'];
    
              $labelProdi[] = $prodi;
              $dataTunggakan[] = $tunggakan;
              $jml_mhs_[] = $jmlMhs;
            }
    
            $finalResponse = [
                'series' => [
                    [
                        'name' => 'Jumlah Tunggakan SKS & Ujian',
                        'type' => 'area',
                        'data' => $dataTunggakan,
                    ],
                    [
                        'name' => 'Jumlah Mahasiswa',
                        'type' => 'line',
                        'data' => $jml_mhs_,
                    ],
                ],
    
                'label' => $labelProdi,
            ];
    
            return new ApiResource(true, 'Jumlah Tunggakan Mahasiswa (SKS & UJIAN) PerProdi', $finalResponse);
      } else {
        return response()->json(['message' => 'value key param not allowed. must be [all, spp, sksujian]' ]);
      }

    
    }

    public function kepegawain(Request $request){
      $key = $request->key;
      if(empty($key) || $key == null){
        return response()->json(['message' => 'key param required.']);
      }
      try {
        if($key === 'pengajar'){
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

  
          return new ApiResource(true, 'Chart Tenaga Dosen Pengajar Perprodi', $finalResponse);
        } else if($key == 'tendik'){
          $data = Simpeg_Pegawai::select('adm_lookup.lookup_id', 'adm_lookup.lookup_value', \DB::raw('COUNT(simpeg_pegawai.id) as total'))
          ->join('adm_lookup', 'adm_lookup.lookup_id', '=', 'simpeg_pegawai.division')
          ->where('adm_lookup.lookup_name', 'DIVISION')
          ->where('simpeg_pegawai.klasi_pegawai', 'TENAGA KEPENDIDIKAN')
          ->where('simpeg_pegawai.status_kerja', 'AKTIF')
          ->groupBy('adm_lookup.lookup_id', 'adm_lookup.lookup_value')
          ->get();

          $dataTotalTendik = [];
          $labelProdi = [];

          foreach($data as $item){
            $prodi = $item['lookup_value'];
            $total = $item['total'];
  
            $labelProdi[] = $prodi;
            $dataTotalTendik[] = $total;
          }

          $finalResponse = [
            'series' => [
              [
                  'name' => 'Total Data Tenaga Pendidik',
                  'type' => 'column',
                  'data' => $dataTotalTendik,
              ],
              
          ],
            
            'label' => $labelProdi
          ];

  
          return new ApiResource(true, 'Chart Tenaga Pendidik Perprodi', $finalResponse);
        } else {
          return response()->json(['message' => 'value key param not allowed. must be [pengajar, tendik]' ]);
        }
       
  
      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function strukturalChart(){
      try {
        $data = Simpeg_Pegawai::select('simpeg_pegawai.golongan AS structural')
          ->selectRaw('COUNT(*) as total_dosen')
          ->join('adm_lookup', 'adm_lookup.lookup_id', '=', 'simpeg_pegawai.division')
          ->where('adm_lookup.lookup_name', 'DIVISION')
          ->where('simpeg_pegawai.klasi_pegawai', 'PENDIDIK (DOSEN)')
          ->where('adm_lookup.lookup_id', '!=', 'AKADEMIK')
          ->where('simpeg_pegawai.status_kerja', 'AKTIF')
          ->groupBy('simpeg_pegawai.golongan')
          ->get();


        $finalResponse = [
            'series' => [
                [
                    'name' => 'Total Data Struktural',
                    'type' => 'column',
                    'data' => $data->pluck('total_dosen')->toArray(),
                ],
            ],
            'label' => $data->pluck('structural')->toArray(),
        ];

        return new ApiResource(true, 'Chart Struktural', $finalResponse);


      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function jmlMbkmBkpl(Request $request){
      try {
        $academicYear = $request->tahun_akademik; 

        $data = BkplPrograms::select('bkpl_events.academic_year', 'bkpl_events.semester', 'bkpl_programs.name', \DB::raw('COUNT(bkpl_events.id) as total'))
            ->join('bkpl_events', 'bkpl_programs.code', '=', 'bkpl_events.program')
            ->where('bkpl_events.academic_year', $academicYear) 
            ->groupBy('bkpl_events.academic_year', 'bkpl_events.semester', 'bkpl_programs.name')
            ->get();

        $genapData = [];
        $gasalData = [];
        $labels = [];

        $programData = [];

        foreach ($data as $item) {
            $semester = $item['semester'];
            $total = $item['total'];
            $name = $item['name'];

            if (!isset($programData[$name])) {
                $programData[$name] = [
                    'name' => $name,
                    'genap' => 0,
                    'gasal' => 0,
                ];
            }

            if ($semester == 'GENAP') {
                $programData[$name]['genap'] = $total;
            } elseif ($semester == 'GASAL') {
                $programData[$name]['gasal'] = $total;
            }

            if (!in_array($name, $labels)) {
                $labels[] = $name;
            }
        }

        foreach ($labels as $name) {
            $genapData[] = $programData[$name]['genap'];
            $gasalData[] = $programData[$name]['gasal'];
        }

        $finalResponse = [
            'series' => [
                [
                    'name' => 'Genap',
                    'type' => 'column',
                    'data' => $genapData,
                ],
                [
                    'name' => 'Gasal',
                    'type' => 'column',
                    'data' => $gasalData,
                ],
            ],
            'label' => $labels,
        ];

        return $finalResponse;
      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }
    
    public function beasiswaChart(Request $request){
      $key = $request->key;

      if(empty($key) || $key == null){
        return response()->json(['message' => 'key param required.']);
      }

      try {
        if($key == "prodi"){
          $data = Pmb_Candidate::select('pmb_candidate.registration_no', 'pmb_candidate.student_code', 'pmb_candidate.name', 'siak_department.name AS prodi', 'pmb_registration_beasiswa.academic_year', 'pmb_beasiswa.name AS jenis_beasiswa', 'pmb_registration_beasiswa.tgl_penetapan')
          ->join('pmb_registration_beasiswa', 'pmb_candidate.registration_no', '=', 'pmb_registration_beasiswa.registration_no')
          ->join('pmb_beasiswa', 'pmb_beasiswa.id', '=', 'pmb_registration_beasiswa.beasiswa_id')
          ->join('siak_department', 'siak_department.code', '=', 'pmb_candidate.department_code')
          ->where('pmb_registration_beasiswa.penetapan', 'Y')
          ->where('pmb_registration_beasiswa.academic_year', '2023/2024')
          ->get();

          $prodiCounts = [];

          $possibleProdiList = Siak_Departemen::pluck('name')->all();

          $prodiCounts = array_fill_keys($possibleProdiList, 0);

          foreach ($data as $row) {
              $prodi = $row['prodi'];
              $prodiCounts[$prodi]++;
          }

          $finalResponse = [
              'series' => [
                  [
                      'name' => 'Total Mahasiswa Penerima beasiswa Per Prodi',
                      'type' => 'column',
                      'data' => array_values($prodiCounts),
                  ],
              ],
              'label' => array_keys($prodiCounts),
          ];


          sort($finalResponse['label']);

          return new ApiResource(true, 'Chart beasiswa', $finalResponse);
        } else if($key == 'jenis'){
            $data = Pmb_Candidate::select(
              'pmb_candidate.registration_no',
              'pmb_candidate.student_code',
              'pmb_candidate.name',
              'pmb_beasiswa.name AS jenis_beasiswa'
              )
              ->join('pmb_registration_beasiswa', 'pmb_candidate.registration_no', '=', 'pmb_registration_beasiswa.registration_no')
              ->join('pmb_beasiswa', 'pmb_beasiswa.id', '=', 'pmb_registration_beasiswa.beasiswa_id')
              ->where('pmb_registration_beasiswa.penetapan', 'Y')
              ->where('pmb_registration_beasiswa.academic_year', '2023/2024')
              ->get();

            $beasiswaCounts = [];

            foreach ($data as $row) {
                $jenis_beasiswa = $row['jenis_beasiswa'];
                if (!isset($beasiswaCounts[$jenis_beasiswa])) {
                    $beasiswaCounts[$jenis_beasiswa] = 0;
                }
                $beasiswaCounts[$jenis_beasiswa]++;
            }

            $finalResponse = [
                'series' => [
                    [
                        'name' => 'Total Mahasiswa Penerima Beasiswa Per Jenis Beasiswa',
                        'type' => 'column',
                        'data' => array_values($beasiswaCounts),
                    ],
                ],
                'label' => array_keys($beasiswaCounts),
            ];

            return new ApiResource(true, 'Chart beasiswa', $finalResponse);
        } else {
          return response()->json(['message' => 'value key param not allowed. must be [prodi, jenis]' ]);
        }
      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function mhsSppChart(Request $request){
      try {
          $data = Siak_Student::select('siak_student.code', 'siak_department.name AS prodi')
              ->join('siak_fee_payment', 'siak_student.code', '=', 'siak_fee_payment.student_code')
              ->join('siak_department', 'siak_department.code', '=', 'siak_student.department_code')
              ->where('siak_fee_payment.academic_year', '2023/2024')
              ->where('siak_fee_payment.semester', 'GASAL')
              ->where('siak_fee_payment.paid', 'Y')
              ->where('siak_fee_payment.fee_item', '1020')
              ->get();
  
          $dataBelum = Siak_Student::select('siak_student.code', 'siak_department.name AS prodi')
              ->join('siak_fee_payment', 'siak_student.code', '=', 'siak_fee_payment.student_code')
              ->join('siak_department', 'siak_department.code', '=', 'siak_student.department_code')
              ->where('siak_fee_payment.academic_year', '2023/2024')
              ->where('siak_fee_payment.semester', 'GASAL')
              ->where('siak_fee_payment.paid', 'N')
              ->where('siak_fee_payment.fee_item', '1020')
              ->get();
  
          $prodiCounts = [];
          $prodiCountsBelum = [];
  
          $labels = Siak_Departemen::pluck('name')->all();
  
          $prodiCounts = array_fill_keys($labels, 0);
          $prodiCountsBelum = array_fill_keys($labels, 0);
  
          foreach ($data as $row) {
              $prodi = $row->prodi;
              $prodiCounts[$prodi]++;
          }
  
          foreach ($dataBelum as $row) {
              $prodi = $row->prodi;
              $prodiCountsBelum[$prodi]++;
          }
  
          $finalResponse = [
              'series' => [
                  [
                      'name' => 'Total Mahasiswa Yang Sudah Bayar SPP Per Prodi',
                      'type' => 'column',
                      'data' => array_values($prodiCounts),
                  ],
                  [
                      'name' => 'Total Mahasiswa Yang Belum Bayar SPP Per Prodi',
                      'type' => 'column',
                      'data' => array_values($prodiCountsBelum),
                  ],
              ],
              'label' => $labels,
          ];
  
          return new ApiResource(true, 'Chart Mhs SPP', $finalResponse);
  
      } catch (\Exception $e) {
          return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function chartIpk(Request $request){
      $tahunAkademik = $request->tahun_akademik;
      $semester = strtoupper($request->semester);
      $angkatan = $request->angkatan;

      $angkatanArray = explode('/', $angkatan);

      $angkatanString = "('" . implode("','", $angkatanArray) . "')";

        try {
            $data = Siak_Student_Snapshot::select('siak_department.code', 'siak_department.name AS prodi', 'siak_student_academic_snapshot.academic_year', \DB::raw('SUBSTRING(AVG(siak_student_academic_snapshot.ipk), 1, 4) AS total'))
                ->join('siak_student', 'siak_student.code', '=', 'siak_student_academic_snapshot.student_code')
                ->join('siak_department', 'siak_department.code', '=', 'siak_student.department_code')
                ->whereRaw("SUBSTRING(siak_student_academic_snapshot.student_code, 1, 2) IN $angkatanString")
                ->where('siak_student_academic_snapshot.academic_year', $tahunAkademik)
                ->where('siak_student_academic_snapshot.semester', $semester)
                ->groupBy('siak_department.code', 'siak_department.name', 'siak_student_academic_snapshot.academic_year')
                ->orderBy('siak_department.code')
                ->orderBy('siak_student_academic_snapshot.academic_year', 'asc')
                ->get();
    
            $ipkData = $data->pluck('total')->map(function ($ipk) {
                return floatval($ipk);
            })->toArray();
    
            $prodiLabels = $data->pluck('prodi')->toArray();
    
            $finalResponse = [
                'series' => [
                    [
                        'name' => 'Data IPK Mahasiswa Per Prodi',
                        'type' => 'column',
                        'data' => $ipkData,
                    ],
                ],
                'label' => $prodiLabels,
            ];
    
            return new ApiResource(true, 'Chart IPK', $finalResponse);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function chartLamaLulusan(Request $request){
      $tahunAkademik = $request->tahun_akademik;
      $semester = $request->semester;
      $angkatan = $request->angkatan;
      
      $angkatan = $request->angkatan;

      $angkatanArray = explode('/', $angkatan);

      $angkatanString = "('" . implode("','", $angkatanArray) . "')";

        try {
          $data = DB::select("
          SELECT a.code, MAX(a.name) as name, a.sex, b.name AS prodi, a.ipk, a.thesis_title, a.registered_date, a.graduated_date, SUBSTRING(DATEDIFF(a.graduated_date, a.registered_date) / 365, 1, 3) AS lama_lulus, IF(SUBSTRING(DATEDIFF(a.graduated_date, a.registered_date) / 365, 1, 3) <= 4, 'Lulus Tepat Waktu', 'Lulus Tidak Tepat Waktu') AS keterangan
          FROM siak_student a
          INNER JOIN siak_department b ON b.code = a.department_code
          INNER JOIN siak_fee_payment c ON c.student_code = a.code
          WHERE SUBSTRING(a.code, 1, 2) IN $angkatanString
          AND a.status = 'GRADUATED'
          AND c.academic_year = ?
          AND c.semester = ?
          GROUP BY a.code, a.sex, b.name, a.ipk, a.thesis_title, a.registered_date, a.graduated_date
      ", [$tahunAkademik, $semester]);
    
            $prodiData = [];
    
            foreach ($data as $item) {
                $prodi = $item->prodi;
                $keterangan = $item->keterangan;
                $lamaLulus = floatval($item->lama_lulus);
    
                if (!isset($prodiData[$prodi])) {
                    $prodiData[$prodi] = [
                        'Lulus Tepat Waktu' => 0,
                        'Lulus Tidak Tepat Waktu' => 0,
                        'Total Lama Lulus' => 0,
                        'Total Lulusan' => 0,
                    ];
                }
    
                $prodiData[$prodi][$keterangan]++;
                $prodiData[$prodi]['Total Lama Lulus'] += $lamaLulus;
                $prodiData[$prodi]['Total Lulusan']++;
            }
    
            $chartData = [
                'series' => [
                    [
                        'name' => 'Lulus Tidak Tepat Waktu',
                        'type' => 'column',
                        'data' => [],
                    ],
                    [
                        'name' => 'Lulus Tepat Waktu',
                        'type' => 'column',
                        'data' => [],
                    ],
                    [
                        'name' => 'Rata-rata Lama Lulus',
                        'type' => 'column',
                        'data' => [],
                    ],
                ],
                'label' => [],
            ];
    
            foreach ($prodiData as $prodi => $values) {
                $chartData['label'][] = $prodi;
                $chartData['series'][0]['data'][] = $values['Lulus Tidak Tepat Waktu'];
                $chartData['series'][1]['data'][] = $values['Lulus Tepat Waktu'];
                $chartData['series'][2]['data'][] = round($values['Total Lama Lulus'] / $values['Total Lulusan'], 2);
            }
    
            return response()->json($chartData);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function chartJafung(Request $request){
      $key = $request->key;
      $status_kerja = strtoupper($request->status_kerja);


      if(empty($key) || $key == null){
        return response()->json(['message' => 'key param required.']);
      }
      if(empty($status_kerja) || $status_kerja == null){
        return response()->json(['message' => 'status_kerja param required.']);
      }

      try {
          $data = Simpeg_Pegawai::select('adm_lookup.lookup_id', 'adm_lookup.lookup_value', 'simpeg_pegawai.status_pegawai', 'simpeg_pegawai.nama', 'simpeg_pegawai.status_kerja', 'simpeg_pegawai.status_sipil', 'simpeg_pegawai.jabatan_fungsional')
              ->join('adm_lookup', 'adm_lookup.lookup_id', '=', 'simpeg_pegawai.division')
              ->where('adm_lookup.lookup_name', 'DIVISION')
              ->where('simpeg_pegawai.klasi_pegawai', 'PENDIDIK (DOSEN)')
              ->where('adm_lookup.lookup_id', '!=', 'AKADEMIK')
              ->where('simpeg_pegawai.status_kerja', $status_kerja)
              ->get();

          if($key == 'lookupvalue'){
            $lookupValues = $data->pluck('lookup_value')->unique()->values()->toArray();
            foreach ($lookupValues as $lookup) {
              $totalDataLookupValue[] = $data->where('lookup_value', $lookup)->count();
            }
            $finalResponse = [
              'series' => [
                  [
                      'name' => 'Total Data lookup_value',
                      'type' => 'column',
                      'data' => $totalDataLookupValue,
                  ],
                 
              ],
              'label' => $lookupValues,
            ];
  
            return new ApiResource(true, 'Chart lookup Value', $finalResponse);

          } else if($key == 'jabatan'){
            $jabatanFungsional = $data->pluck('jabatan_fungsional')->unique()->values()->toArray();
          
            $jabatanFungsional = array_map(function ($item) {
              return empty($item) ? '-' : $item;
            }, $jabatanFungsional);
    
            $totalDataLookupValue = [];
            $totalDataJafung = [];
  
          
  
            foreach ($jabatanFungsional as $jafung) {
              if ($jafung === '-') {
                  $totalDataJafung[] = $data->filter(function ($item) use ($jafung) {
                      return empty($item['jabatan_fungsional']);
                  })->count();
              } else {
                  $totalDataJafung[] = $data->where('jabatan_fungsional', $jafung)->count();
              }
            }
  
            $finalResponse = [
                'series' => [
                    [
                        'name' => 'Total Data jabatan fungsional',
                        'type' => 'column',
                        'data' => $totalDataJafung,
                    ],
                ],
                'label' => $jabatanFungsional,
            ];
    
            return new ApiResource(true, 'Chart Jafung', $finalResponse);
          } else {
            return response()->json(['message' => 'value key param not allowed. must be [lookupvalue, jabatan]' ]);
          }
      } catch (\Exception $e) {
          return response()->json(['error' => $e->getMessage()], 500);
      }
    }
}


