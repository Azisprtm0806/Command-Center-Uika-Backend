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

            $dataPeminatProvinsi = Pmb_Provinsi::select('pmb_provinsi.name as provinsi')
            ->selectRaw('COUNT(pmb_registration.registration_no) as total')
            ->join('pmb_candidate', 'pmb_provinsi.id', '=', 'pmb_candidate.prov_code')
            ->join('pmb_registration', 'pmb_candidate.registration_no', '=', 'pmb_registration.registration_no')
            ->join('pmb_registration_payment', 'pmb_registration.registration_no', '=', 'pmb_registration_payment.registration_no')
            ->whereIn('pmb_registration_payment.fee_item', ['1000', '1001', '1002', '1003'])
            ->where('pmb_registration.academic_year', '2023/2024')
            ->where('pmb_registration.semester', 'GASAL')
            ->groupBy('pmb_provinsi.name')
            ->orderBy('pmb_provinsi.name', 'ASC')
            ->get();
            
            $totalDaftarMhs = [];
            $totalDiterimaMhs = [];
            $totalPeminatMhs = [];
            $labels = [];

            $provinsiListDaftar = [];
            $provinsiListDiterima = [];
            $provinsiListPeminat = [];

            foreach ($dataPerProvinsiDaftar as $item) {
                $provinsiListDaftar[$item['provinsi']] = $item['total'];
            }

            foreach ($dataPerProvinsiDiterima as $item) {
                $provinsiListDiterima[$item['provinsi']] = $item['total'];
            }

            foreach ($dataPeminatProvinsi as $item) {
                $provinsiListPeminat[$item['provinsi']] = $item['total'];
            }

            $combinedProvinces = array_merge($provinsiListDaftar, $provinsiListDiterima, $provinsiListPeminat);

            $labels = array_keys($combinedProvinces);

            foreach ($labels as $provinsi) {
                $totalDaftarMhs[] = $provinsiListDaftar[$provinsi] ?? 0;
                $totalDiterimaMhs[] = $provinsiListDiterima[$provinsi] ?? 0;
                $totalPeminatMhs[] = $provinsiListPeminat[$provinsi] ?? 0;
            }
            

            $totalDiterimaMhs = array_map('intval', $totalDiterimaMhs);
            $totalPeminatMhs = array_map('intval', $totalPeminatMhs);

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
                  'data' => $totalPeminatMhs
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
          $dataRataRata = Siak_Student_Snapshot::select('siak_department.code', 'siak_department.name AS prodi', 'siak_student_academic_snapshot.academic_year', \DB::raw('SUBSTRING(AVG(siak_student_academic_snapshot.ipk), 1, 4) AS total'))
              ->join('siak_student', 'siak_student.code', '=', 'siak_student_academic_snapshot.student_code')
              ->join('siak_department', 'siak_department.code', '=', 'siak_student.department_code')
              ->whereRaw("SUBSTRING(siak_student_academic_snapshot.student_code, 1, 2) IN $angkatanString")
              ->where('siak_student_academic_snapshot.academic_year', $tahunAkademik)
              ->where('siak_student_academic_snapshot.semester', $semester)
              ->groupBy('siak_department.code', 'siak_department.name', 'siak_student_academic_snapshot.academic_year')
              ->orderBy('siak_department.code')
              ->orderBy('siak_student_academic_snapshot.academic_year', 'asc')
              ->get();
  
              $dataMinMax = DB::select("
                  SELECT c.code, c.name AS prodi, a.academic_year, SUBSTRING(MIN(a.ipk), 1, 4) AS minimum, SUBSTRING(MAX(a.ipk), 1, 4) AS maximum
                  FROM siak_student_academic_snapshot a
                  INNER JOIN siak_student b ON b.code=a.student_code
                  INNER JOIN siak_department c ON c.code=b.department_code
                  WHERE SUBSTRING(a.student_code, 1, 2) IN $angkatanString
                  AND a.ipk > 0
                  AND a.academic_year = '$tahunAkademik' 
                  AND a.semester = '$semester'
                  GROUP BY c.code, c.name, a.academic_year
                  ORDER BY c.code, a.academic_year ASC
              ");

          $prodiLabels = $dataRataRata->pluck('prodi')->unique();
  
          $ipkDataRataRata = $dataRataRata->pluck('total')->map(function ($ipk) {
              return floatval($ipk);
          })->toArray();
  
          $minData = [];
          $maxData = [];
  
          foreach ($prodiLabels as $label) {
            $minData[] = in_array($label, array_column($dataMinMax, 'prodi')) ? floatval($dataMinMax[array_search($label, array_column($dataMinMax, 'prodi'))]->minimum) : 0;
            $maxData[] = in_array($label, array_column($dataMinMax, 'prodi')) ? floatval($dataMinMax[array_search($label, array_column($dataMinMax, 'prodi'))]->maximum) : 0;
            
          }
  
          $finalResponse = [
              'series' => [
                  [
                      'name' => 'Min',
                      'type' => 'column',
                      'data' => $minData,
                  ],
                  [
                      'name' => 'Rata Rata',
                      'type' => 'column',
                      'data' => $ipkDataRataRata,
                  ],
                  [
                      'name' => 'Max',
                      'type' => 'column',
                      'data' => $maxData,
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
      $tahun = $request->tahun;
      $angkatan = $request->angkatan;
  
      $angkatanArray = explode('/', $angkatan);
  
      $angkatanString = "('" . implode("','", $angkatanArray) . "')";

      if(empty($tahun) || $tahun == null){
        return response()->json(['message' => 'tahun param required.']);
      }
      if(empty($angkatan) || $angkatan == null){
        return response()->json(['message' => 'angk$angkatan param required.']);
      }

        try {
          $students = DB::select("
            SELECT a.code, a.name, a.sex, b.name AS prodi, a.ipk, a.thesis_title, 
                   CONCAT(YEAR(a.registered_date), '-09-01') AS tahun_daftar, a.graduated_date, 
                   YEAR(a.graduated_date) AS tahun, 
                   SUBSTRING(DATEDIFF(a.graduated_date, CONCAT(YEAR(a.registered_date), '-09-01'))/365, 1, 3) AS lama_lulus, 
                   IF(SUBSTRING(DATEDIFF(a.graduated_date, CONCAT(YEAR(a.registered_date), '-09-01'))/365, 1, 3) <= 4, 'Lulus Tepat Waktu', 'Lulus Tidak Tepat Waktu') AS keterangan
            FROM siak_student a
            INNER JOIN siak_department b ON b.code = a.department_code
            WHERE SUBSTRING(a.code, 1, 2) IN $angkatanString
                AND a.status = 'GRADUATED'
                AND YEAR(a.graduated_date) = $tahun
            GROUP BY a.code, a.name, a.sex, b.name, a.ipk, a.thesis_title, 
                     a.graduated_date, tahun_daftar, tahun, lama_lulus, keterangan
        ");

        $prodiCounts = [];
        $labels = [];
        $averageLamaLulusan = [];
        
        foreach ($students as $student) {
            $prodi = $student->prodi;
            $lamaLulus = (float) $student->lama_lulus; 
        
            if (!array_key_exists($prodi, $prodiCounts)) {
                $prodiCounts[$prodi] = 1;
                $averageLamaLulusan[$prodi] = $lamaLulus; 
                $labels[] = $prodi;
            } else {
                $prodiCounts[$prodi]++;
                $averageLamaLulusan[$prodi] += $lamaLulus;
            }
        }
        
        foreach ($averageLamaLulusan as $prodi => $totalLamaLulusan) {
            $averageLamaLulusan[$prodi] = $totalLamaLulusan / $prodiCounts[$prodi];
        }
        
        $data = [
            'series' => [
                [
                    'name' => 'Total Data Mhs Per Prodi',
                    'type' => 'column',
                    'data' => array_values($prodiCounts),
                ],
                [
                    'name' => 'Rata-rata Lama Lulusan',
                    'type' => 'column',
                    'data' => array_values($averageLamaLulusan), // Menambahkan data rata-rata lama lulusan
                ],
            ],
            'labels' => $labels,
        ];
        
        return new ApiResource(true, 'Chart lama lulusan', $data);
        
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

    // public function chartJafungNew(Request $request)
    // {
    //     $data = Simpeg_Pegawai::select('adm_lookup.lookup_id', 'adm_lookup.lookup_value', 'simpeg_pegawai.jabatan_fungsional')
    //         ->join('adm_lookup', 'adm_lookup.lookup_id', '=', 'simpeg_pegawai.division')
    //         ->where('adm_lookup.lookup_name', 'DIVISION')
    //         ->where('simpeg_pegawai.klasi_pegawai', 'PENDIDIK (DOSEN)')
    //         ->where('adm_lookup.lookup_id', '!=', 'AKADEMIK')
    //         ->where('simpeg_pegawai.status_kerja', 'AKTIF')
    //         ->get();
    
    //     $lookupValues = $data->pluck('lookup_value')->unique()->values();
    //     $jabatanFungsionalValues = $data->pluck('jabatan_fungsional')->unique()->values();
    //     $none;
        
    //     $chartData = [
    //         'series' => [],
    //         'label' => $lookupValues->toArray(),
    //     ];
    
    //     foreach ($jabatanFungsionalValues as $jabatan) {
    //         $seriesData = [];
    //         // if($jabatan == "(none)" || $jabatan == ""){
    //         //   $count1 = $data->where('jabatan_fungsional', "(none)")->count();
    //         //   $count2 = $data->where('jabatan_fungsional', "")->count();
    //         //   $none = $count1 + $count2;
    //         //   $jabatan = $none;
    //         //   $chartData['label'] = "(none)";
    //         // }
    //         foreach ($lookupValues as $lookupValue) {
    //             $count = $data->where('jabatan_fungsional', $jabatan)->where('lookup_value', $lookupValue)->count();
    //             $seriesData[] = $count;
    //         }
    //         $chartData['series'][] = [
    //             'name' => $jabatan,
    //             'data' => $seriesData,
    //         ];
    //     }
    
    //     return response()->json($chartData);
    // }

    

    

    public function chartJafungProdi(Request $request){
      $prodi = $request->prodi;
      try {
          $data = Simpeg_Pegawai::select('jabatan_fungsional', DB::raw('COUNT(nip) AS jumlah'))
              ->where('klasi_pegawai', 'PENDIDIK (DOSEN)')
              ->where('status_kerja', 'AKTIF')
              ->where('division', $prodi)
              ->groupBy('jabatan_fungsional')
              ->orderBy('jabatan_fungsional')
              ->get();
  
          $dataPoints = [];
          $labels = [];
  
          foreach ($data as $item) {
              $labels[] = $item->jabatan_fungsional;
              $dataPoints[] = $item->jumlah;
          }
  
          $finalResponse = [
              'series' => [
                  [
                      'name' => 'Total Data Jafung',
                      'type' => 'column',
                      'data' => $dataPoints,
                  ],
              ],
              'label' => $labels,
          ];
  
          return new ApiResource(true, 'Chart Struktural', $finalResponse);
      } catch (\Exception $e) {
          return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function paiChart(Request $request)
    {
        try {
            $data = Simpeg_Pegawai::select('adm_lookup.lookup_id', 'adm_lookup.lookup_value', 'simpeg_pegawai.jabatan_fungsional')
                ->join('adm_lookup', 'adm_lookup.lookup_id', '=', 'simpeg_pegawai.division')
                ->where('adm_lookup.lookup_name', 'DIVISION')
                ->where('simpeg_pegawai.klasi_pegawai', 'PENDIDIK (DOSEN)')
                ->where('adm_lookup.lookup_id', '!=', 'AKADEMIK')
                ->where('simpeg_pegawai.status_kerja', 'AKTIF')
                ->get();
    
            $jabatanFungsionalValues = $data->pluck('jabatan_fungsional')->unique()->values();
            $jmlJafung = [];
            $labels = [];
            $none;
    
            foreach ($jabatanFungsionalValues as $jabatan) {
                $count = $data->where('jabatan_fungsional', $jabatan)->count();

                if($jabatan == "(none)" || $jabatan == ""){
                  
                  if(!isset($none)){
                    $count1 = $data->where('jabatan_fungsional', "(none)")->count();
                    $count2 = $data->where('jabatan_fungsional', "")->count();
                    $none = $count1 + $count2;
                    $jmlJafung[] = $none;
                    $labels[] = "NON FUNGSIONAL";
                  }
                } else {
                $count = $data->where('jabatan_fungsional', $jabatan)->count();


                  $jmlJafung[] = $count;
                  $labels[] = $jabatan;
                }
            }
    
            $finalResponse = [
                'series' => [
                    [
                        'name' => 'Jumlah Jafung',
                        'type' => 'column',
                        'data' => $jmlJafung,
                    ],
                ],
                'label' => $labels,
            ];
    
            return new ApiResource(true, 'Chart Pai', $finalResponse);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // public function chartJafungNew(Request $request){
    //   $data = Simpeg_Pegawai::select('adm_lookup.lookup_id', 'adm_lookup.lookup_value', 'simpeg_pegawai.jabatan_fungsional')
    //       ->join('adm_lookup', 'adm_lookup.lookup_id', '=', 'simpeg_pegawai.division')
    //       ->where('adm_lookup.lookup_name', 'DIVISION')
    //       ->where('simpeg_pegawai.klasi_pegawai', 'PENDIDIK (DOSEN)')
    //       ->where('adm_lookup.lookup_id', '!=', 'AKADEMIK')
    //       ->where('simpeg_pegawai.status_kerja', 'AKTIF')
    //       ->get();
  
    //   $lookupValues = $data->pluck('lookup_value')->unique()->values();
    //   $jabatanFungsionalValues = $data->pluck('jabatan_fungsional')->unique()->values();
  
    //   $chartData = [
    //       'series' => [],
    //       'label' => $lookupValues->toArray(),
    //   ];
  
    //   $noneData = [];
  
    //   foreach ($jabatanFungsionalValues as $jabatan) {
    //       $seriesData = [];
    //       foreach ($lookupValues as $lookupValue) {
    //           if ($jabatan == "(none)" || $jabatan == "") {
    //               $count = $data->where('jabatan_fungsional', $jabatan)->where('lookup_value', $lookupValue)->count();
    //               $seriesData[] = $count;
    //           } else {
    //               $count = $data->where('jabatan_fungsional', $jabatan)->where('lookup_value', $lookupValue)->count();
    //               $seriesData[] = $count;
    //           }
    //       }
  
    //       if ($jabatan == "(none)" || $jabatan == "") {
    //           $noneData = array_map(function ($a, $b) {
    //               return $a + $b;
    //           }, $noneData, $seriesData);
    //       } else {
    //           $chartData['series'][] = [
    //               'name' => $jabatan,
    //               'data' => $seriesData,
    //           ];
    //       }
    //   }
    //   $chartData['series'][] = [
    //       'name' => 'NON FUNGSIONAL',
    //       'data' => $noneData,
    //   ];
  
    //   return response()->json($chartData);
    // }

    public function chartJafungNew(Request $request)
{
    $data = Simpeg_Pegawai::select('adm_lookup.lookup_id', 'adm_lookup.lookup_value', 'simpeg_pegawai.jabatan_fungsional', 'simpeg_pegawai.nama')
        ->join('adm_lookup', 'adm_lookup.lookup_id', '=', 'simpeg_pegawai.division')
        ->where('adm_lookup.lookup_name', 'DIVISION')
        ->where('simpeg_pegawai.klasi_pegawai', 'PENDIDIK (DOSEN)')
        ->where('adm_lookup.lookup_id', '!=', 'AKADEMIK')
        ->where('simpeg_pegawai.status_kerja', 'AKTIF')
        ->get();

    $lookupValues = $data->pluck('lookup_value')->unique()->values();
    $jabatanFungsionalValues = $data->pluck('jabatan_fungsional')->unique()->values();
  
    $namaDosen = $data->pluck('nama')->unique()->values()->toArray();

    $chartData = [
        'series' => [],
        'label' => $lookupValues->toArray(),
    ];

    $noneData = [];
    $namaDosenData = [];

    foreach ($jabatanFungsionalValues as $jabatan) {
        $seriesData = [];
        $namaDosenSeries = [];
        foreach ($lookupValues as $lookupValue) {
            if ($jabatan == "(none)" || $jabatan == "") {
                $count = $data->where('jabatan_fungsional', $jabatan)->where('lookup_value', $lookupValue)->count();
                $seriesData[] = $count;
            } else {
                $count = $data->where('jabatan_fungsional', $jabatan)->where('lookup_value', $lookupValue)->count();
                $seriesData[] = $count;
            }
            // Menambahkan nama dosen ke dalam array namaDosenSeries
            $namaDosenCount = $data->where('jabatan_fungsional', $jabatan)->where('lookup_value', $lookupValue)->pluck('nama')->toArray();
            $namaDosenSeries[] = $namaDosenCount;
        }

        if ($jabatan == "(none)" || $jabatan == "") {
            $noneData = array_map(function ($a, $b) {
                return $a + $b;
            }, $noneData, $seriesData);
        } else {
            $chartData['series'][] = [
                'name' => $jabatan,
                'data' => $seriesData,
            ];
        }
        
        // Menambahkan nama dosen ke dalam array namaDosenData
        $namaDosenData[] = $namaDosenSeries;
    }
    $chartData['series'][] = [
        'name' => 'NON FUNGSIONAL',
        'data' => $noneData,
    ];
  
    // Menambahkan data nama dosen
    $chartData['series'][] = [
        'name' => 'Nama Dosen',
        'data' => $namaDosenData,
    ];

    return response()->json($chartData);
}

    
}
