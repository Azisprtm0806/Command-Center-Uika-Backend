<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pmb_Candidate;
use App\Models\Pmb_Desa;
use App\Models\Pmb_Provinsi;
use App\Models\Pmb_Registration;
use App\Models\Siak_Student;
use App\Models\Siak_Student_Snapshot;
use Yajra\Datatables\Datatables;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AkademikController extends Controller
{
    public function totalMhsDaftar(Request $request){
        $tahunAkademik = $request->tahun_akademik;
        $semester = strtoupper($request->semester);

        try {
          $data = Pmb_Registration::join('pmb_registration_payment AS b', 'b.registration_no', '=', 'pmb_registration.registration_no')
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
      
          return Datatables::of($data)->addIndexColumn()->make(true);
          
        }catch (\Exception $e) {
          return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function totalMhsDiterima(Request $request){
      $tahunAkademik = $request->tahun_akademik;
      $semester = strtoupper($request->semester);

      try {
        $data = Pmb_Registration::join('pmb_registration_payment AS b', 'b.registration_no', '=', 'pmb_registration.registration_no')
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
        
          return Datatables::of($data)->addIndexColumn()->make(true);


      }catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function totalMhsPeminat(Request $request){
      try {
        $data = DB::table('pmb_registration as a')
          ->join('pmb_registration_payment as b', 'b.registration_no', '=', 'a.registration_no')
          ->join('pmb_candidate as c', 'c.registration_no', '=', 'a.registration_no')
          ->join('siak_department as d', 'd.code', '=', 'a.department_code')
          ->join('siak_faculty as e', 'e.code', '=', 'd.faculty_code')
          ->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
          ->where('a.academic_year', '2023/2024')
          ->where('a.semester', 'GASAL')
          ->groupBy('e.name', 'd.name') 
          ->select('e.name as fakultas', 'd.name as prodi', DB::raw('COUNT(a.registration_no) as total'))
          ->orderBy('e.code')
          ->orderBy('d.npm_code', 'ASC')
          ->get();
        
          return Datatables::of($data)->addIndexColumn()->make(true);
      }catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function mhsDaftarDetail(Request $request){
        try {
            $data = DB::select("
            SELECT d.code, MAX(a.registration_no) AS registration_no, MAX(a.candidate_name) AS candidate_name, MAX(a.candidate_phone) AS candidate_phone, MAX(c.sex) AS sex, MAX(c.email) AS email, MAX(c.national_id_number) AS national_id_number, MAX(c.birthplace) AS birthplace, MAX(c.birthdate) AS birthdate, MAX(c.marital_status) AS marital_status, MAX(e.name) AS fakultas, MAX(d.name) AS prodi
            FROM pmb_registration a
            INNER JOIN pmb_registration_payment b ON b.registration_no = a.registration_no
            INNER JOIN pmb_candidate c ON c.registration_no = a.registration_no
            INNER JOIN siak_department d ON d.code = a.department_code
            INNER JOIN siak_faculty e ON e.code = d.faculty_code
            WHERE b.paid='Y' AND b.fee_item IN ('1000', '1001', '1002', '1003')
            AND a.academic_year='2023/2024' AND a.semester='GASAL'
            GROUP BY d.code
            ORDER BY e.code, d.npm_code ASC
            
            ");
    
            return Datatables::of($data)->addIndexColumn()->make(true);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
  
    public function mhsPeminatDetail(Request $request){
      try {
          $data = DB::select("
              SELECT e.name AS fakultas, d.name AS prodi, COUNT(a.registration_no) AS total
              FROM pmb_registration a
              INNER JOIN pmb_registration_payment b ON b.registration_no=a.registration_no
              INNER JOIN pmb_candidate c ON c.registration_no=a.registration_no
              INNER JOIN siak_department d ON d.code=a.department_code
              INNER JOIN siak_faculty e ON e.code=d.faculty_code
              WHERE b.fee_item IN ('1000','1001','1002','1003')
              AND a.academic_year='2023/2024' AND a.semester='GASAL'
              GROUP BY e.name, d.name  -- Tambahkan kolom ini ke dalam GROUP BY
              ORDER BY e.code, d.npm_code ASC
          ");
  
          return Datatables::of($data)->addIndexColumn()->make(true);
      } catch (\Exception $e) {
          return response()->json(['error' => $e->getMessage()], 500);
      }
    }
  
    public function ipkPerProdi(Request $request){
      try {
          $data = Siak_Student_Snapshot::select('siak_department.code', 'siak_department.name AS prodi', 'siak_student_academic_snapshot.academic_year', \DB::raw('SUBSTRING(AVG(siak_student_academic_snapshot.ipk), 1, 4) AS total'))
            ->join('siak_student', 'siak_student.code', '=', 'siak_student_academic_snapshot.student_code')
            ->join('siak_department', 'siak_department.code', '=', 'siak_student.department_code')
            ->whereRaw("SUBSTRING(siak_student_academic_snapshot.student_code, 1, 2) IN ('19','20','21','22')")
            ->where('siak_student_academic_snapshot.academic_year', '2022/2023')
            ->where('siak_student_academic_snapshot.semester', 'GENAP')
            ->groupBy('siak_department.code', 'siak_department.name', 'siak_student_academic_snapshot.academic_year')
            ->orderBy('siak_department.code')
            ->orderBy('siak_student_academic_snapshot.academic_year', 'asc')
            ->get();
        
        return Datatables::of($data)->addIndexColumn()->make(true);
      }catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }
    
    public function ipkPerFakultas(Request $request){
      try {
        $data = Siak_Student_Snapshot::select('siak_department.code', 'siak_department.name AS prodi', 'siak_student_academic_snapshot.academic_year', DB::raw("SUBSTRING(AVG(siak_student_academic_snapshot.ipk), 1, 4) AS total"))
        ->join('siak_student', 'siak_student.code', '=', 'siak_student_academic_snapshot.student_code')
        ->join('siak_department', 'siak_department.code', '=', 'siak_student.department_code')
        ->whereIn(DB::raw("SUBSTRING(siak_student_academic_snapshot.student_code, 1, 2)"), ['19', '20', '21', '22'])
        ->where('siak_student_academic_snapshot.academic_year', '2022/2023')
        ->where('siak_student_academic_snapshot.semester', 'GENAP')
        ->where('siak_department.faculty_code', 'FT')
        ->groupBy('siak_department.code', 'siak_department.name', 'siak_student_academic_snapshot.academic_year')
        ->orderBy('siak_department.code')
        ->orderBy('siak_student_academic_snapshot.academic_year')
        ->get();
    
        
        return Datatables::of($data)->addIndexColumn()->make(true);
      }catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function lamaLulusan(Request $request){
        try {
          $students = DB::select("
            SELECT a.code, a.name, a.sex, b.name AS prodi, a.ipk, a.thesis_title, 
                   CONCAT(YEAR(a.registered_date), '-09-01') AS tahun_daftar, a.graduated_date, 
                   YEAR(a.graduated_date) AS tahun, 
                   SUBSTRING(DATEDIFF(a.graduated_date, CONCAT(YEAR(a.registered_date), '-09-01'))/365, 1, 3) AS lama_lulus, 
                   IF(SUBSTRING(DATEDIFF(a.graduated_date, CONCAT(YEAR(a.registered_date), '-09-01'))/365, 1, 3) <= 4, 'Lulus Tepat Waktu', 'Lulus Tidak Tepat Waktu') AS keterangan
            FROM siak_student a
            INNER JOIN siak_department b ON b.code = a.department_code
            WHERE SUBSTRING(a.code, 1, 2) IN ('19', '20', '21', '22')
                AND a.status = 'GRADUATED'
                AND YEAR(a.graduated_date) = '2023'
                AND b.code = 'FT_TI'
            GROUP BY a.code, a.name, a.sex, b.name, a.ipk, a.thesis_title, 
                     a.graduated_date, tahun_daftar, tahun, lama_lulus, keterangan
        ");
      
           return Datatables::of($students)->addIndexColumn()->make(true);
      
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function mhsPerProvinsi(){
      try {
        $data = Pmb_Provinsi::join('pmb_candidate AS c', 'pmb_provinsi.id', '=', 'c.prov_code')
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
    

        return Datatables::of($data)->addIndexColumn()->make(true);

      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function mhsdDiterimaProvinsiFakultas(){
      try {
        $data = Pmb_Provinsi::leftJoin('pmb_candidate as c', 'pmb_provinsi.id', '=', 'c.prov_code')
              ->leftJoin('pmb_registration as a', 'c.registration_no', '=', 'a.registration_no')
              ->leftJoin('pmb_registration_payment as b', 'a.registration_no', '=', 'b.registration_no')
              ->leftJoin('siak_department as d', 'd.code', '=', 'a.department_code')
              ->leftJoin('siak_faculty as e', 'e.code', '=', 'd.faculty_code')
              ->where('b.paid', 'Y')
              ->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
              ->where('a.academic_year', '2023/2024')
              ->where('a.semester', 'GASAL')
              ->groupBy('pmb_provinsi.id', 'pmb_provinsi.name', 'e.name')
              ->orderBy('pmb_provinsi.name', 'ASC')
              ->select('pmb_provinsi.name as provinsi', 'e.name as fakultas')
              ->selectRaw('COUNT(a.registration_no) as total')
              ->get();
    
          return Datatables::of($data)->addIndexColumn()->make(true);
      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function mhsAktifNonFrs(){
      try {

        $data = Siak_Student::select('siak_student.code', 'siak_student.name', 'siak_department.name AS prodi', 'siak_student.class')
          ->join('siak_fee_payment', 'siak_student.code', '=', 'siak_fee_payment.student_code')
          ->join('siak_department', 'siak_department.code', '=', 'siak_student.department_code')
          ->where('siak_fee_payment.academic_year', '2023/2024')
          ->where('siak_fee_payment.semester', 'GASAL')
          ->where('siak_fee_payment.paid', 'Y')
          ->where('siak_fee_payment.fee_item', '1020')
          ->whereNotIn('siak_student.code', function ($query) {
              $query->select('student_code')
                  ->from('siak_frs')
                  ->where('academic_year', '2023/2024')
                  ->where('semester', 'GASAL');
          })
          ->get();

          return Datatables::of($data)->addIndexColumn()->make(true);


      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function mhsSudahBayarSpp(){
      try {
        $data = Siak_Student::select('siak_student.code', 'siak_student.name', 'siak_department.name AS prodi', 'siak_student.class', 'siak_fee_payment.nominal', 'siak_fee_payment.payment_date', 'siak_student.funding_scheme')
          ->join('siak_fee_payment', 'siak_student.code', '=', 'siak_fee_payment.student_code')
          ->join('siak_department', 'siak_department.code', '=', 'siak_student.department_code')
          ->where('siak_fee_payment.academic_year', '2023/2024')
          ->where('siak_fee_payment.semester', 'GASAL')
          ->where('siak_fee_payment.paid', 'Y')
          ->where('siak_fee_payment.fee_item', '1020')
          ->get();

          return Datatables::of($data)->addIndexColumn()->make(true);



      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function mhsBelumBayarSpp(){
      try {
        $data = Siak_Student::select('siak_student.code', 'siak_student.name', 'siak_department.name AS prodi', 'siak_student.class', 'siak_fee_payment.nominal', 'siak_fee_payment.payment_date', 'siak_student.funding_scheme')
          ->join('siak_fee_payment', 'siak_student.code', '=', 'siak_fee_payment.student_code')
          ->join('siak_department', 'siak_department.code', '=', 'siak_student.department_code')
          ->where('siak_fee_payment.academic_year', '2023/2024')
          ->where('siak_fee_payment.semester', 'GASAL')
          ->where('siak_fee_payment.paid', 'N')
          ->where('siak_fee_payment.fee_item', '1020')
          ->get();

          return Datatables::of($data)->addIndexColumn()->make(true);


      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function mhsLulusBeasiswa(){
      try {
        $data = DB::table('pmb_candidate as a')
          ->select('a.registration_no', 'a.student_code', 'a.name', 'd.name as prodi', 'b.academic_year', 'c.name as jenis_beasiswa', 'b.tgl_penetapan')
          ->join('pmb_registration_beasiswa as b', 'b.registration_no', '=', 'a.registration_no')
          ->join('pmb_beasiswa as c', 'c.id', '=', 'b.beasiswa_id')
          ->join('siak_department as d', 'd.code', '=', 'a.department_code')
          ->where('b.penetapan', 'Y')
          ->where('b.academic_year', '2023/2024')
          ->orderBy('c.name', 'asc')
          ->get();

          return Datatables::of($data)->addIndexColumn()->make(true);

      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function MhsPenerimaBeasiswa(){
      try {
        $data = Pmb_Candidate::select('pmb_candidate.registration_no', 'pmb_candidate.student_code', 'pmb_candidate.name', 'siak_department.name AS prodi', 'pmb_registration_beasiswa.academic_year', 'pmb_beasiswa.name AS jenis_beasiswa', 'pmb_registration_beasiswa.tgl_penetapan')
          ->join('pmb_registration_beasiswa', 'pmb_candidate.registration_no', '=', 'pmb_registration_beasiswa.registration_no')
          ->join('pmb_beasiswa', 'pmb_beasiswa.id', '=', 'pmb_registration_beasiswa.beasiswa_id')
          ->join('siak_department', 'siak_department.code', '=', 'pmb_candidate.department_code')
          ->where('pmb_registration_beasiswa.penetapan', 'Y')
          ->where('pmb_registration_beasiswa.academic_year', '2023/2024')
          ->get();

          return Datatables::of($data)->addIndexColumn()->make(true);

      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function AsalMhs(){
      try {
        $data = Pmb_Registration::select('pmb_registration.*', 'pmb_desa.name', 'pmb_candidate.address')
          ->join('pmb_registration_payment', 'pmb_registration_payment.registration_no', '=', 'pmb_registration.registration_no')
          ->join('pmb_candidate', 'pmb_candidate.registration_no', '=', 'pmb_registration.registration_no')
          ->join('pmb_desa', 'pmb_desa.id', '=', 'pmb_candidate.desa_code')
          ->where('pmb_registration_payment.paid', 'Y')
          ->whereIn('pmb_registration_payment.fee_item', ['1000', '1001', '1002', '1003'])
          ->where('pmb_registration.academic_year', '2023/2024')
          ->where('pmb_registration.semester', 'GASAL')
          ->get();

          return Datatables::of($data)->addIndexColumn()->make(true);

      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function dataMhs(){
      try {
        $data = DB::table('pmb_registration as a')
        ->join('pmb_registration_payment as b', 'b.registration_no', '=', 'a.registration_no')
        ->join('pmb_candidate as c', 'c.registration_no', '=', 'a.registration_no')
        ->join('pmb_provinsi as d', 'd.id', '=', 'c.prov_code')
        ->join('pmb_desa as e', 'e.id', '=', 'c.desa_code')
        ->join('pmb_kabupaten as f', 'f.id', '=', 'c.kabkot_code')
        ->join('pmb_kecamatan as h', 'h.id', '=', 'c.kec_code')
        ->join('siak_department as g', 'g.code', '=', 'a.department_code')
        ->select(
            'c.registration_no',
            'c.name as nama_mahasiswa',
            'c.student_code as npm',
            'c.sex',
            'g.name as prodi',
            'd.name as provinsi',
            'f.name as city',
            'h.name as kecamatan',
            'e.name as desa',
            'c.address'
        )
        ->whereRaw("b.fee_item IN ('1000', '1001', '1002', '1003')")
        ->where('a.academic_year', '2022/2023')
        ->where('c.student_code', '!=', '')
        ->get();


          return Datatables::of($data)->addIndexColumn()->make(true);

      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function dataMhsPeminat(){
      try {
        $students = DB::table('pmb_registration as a')
          ->join('pmb_registration_payment as b', 'b.registration_no', '=', 'a.registration_no')
          ->join('pmb_candidate as c', 'c.registration_no', '=', 'a.registration_no')
          ->join('pmb_provinsi as d', 'd.id', '=', 'c.prov_code')
          ->join('pmb_desa as e', 'e.id', '=', 'c.desa_code')
          ->join('pmb_kabupaten as f', 'f.id', '=', 'c.kabkot_code')
          ->join('pmb_kecamatan as h', 'h.id', '=', 'c.kec_code')
          ->join('siak_department as g', 'g.code', '=', 'a.department_code')
          ->select(
              'c.registration_no',
              'c.name as nama_mahasiswa',
              'c.student_code as npm',
              'c.sex',
              'g.name as prodi',
              'd.name as provinsi',
              'f.name as city',
              'h.name as kecamatan',
              'e.name as desa',
              'c.address'
          )
          ->where(function ($query) {
              $query->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
                  ->where('a.academic_year', '2022/2023')
                  ->where('c.student_code', '');
          })
          ->get();


          return Datatables::of($students)->addIndexColumn()->make(true);

      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }
    
    public function dataAsalSekolahNewMhs(){
      try {     
        $data = Pmb_Registration::select('pmb_registration.registration_no', 'pmb_registration.candidate_name', 'pmb_candidate.sex', 'pmb_education_slta.nama_sekolah', 'pmb_education_slta.address_sekolah')
        ->join('pmb_candidate', 'pmb_candidate.registration_no', '=', 'pmb_registration.registration_no')
        ->join('pmb_education_slta', 'pmb_education_slta.registration_no', '=', 'pmb_candidate.registration_no')
        ->join('siak_department', 'siak_department.code', '=', 'pmb_registration.department_code')
        ->where('pmb_registration.academic_year', '2023/2024')
        ->where('pmb_registration.semester', 'GASAL')
        ->get();

          return Datatables::of($data)->addIndexColumn()->make(true);

      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function dataAsalSekolahOldMhs(){
      try {
          $data = Pmb_Registration::select('pmb_registration.registration_no', 'pmb_registration.candidate_name', 'pmb_candidate.sex', 'pmb_education_slta.nama_sekolah', 'pmb_education_slta.address_sekolah')
          ->join('pmb_candidate', 'pmb_candidate.registration_no', '=', 'pmb_registration.registration_no')
          ->join('pmb_education_slta', 'pmb_education_slta.registration_no', '=', 'pmb_candidate.registration_no')
          ->join('siak_department', 'siak_department.code', '=', 'pmb_registration.department_code')
          ->where('pmb_registration.academic_year', '2023/2024')
          ->where('pmb_registration.semester', 'GASAL')
          ->get();


          return Datatables::of($data)->addIndexColumn()->make(true);

      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function petaSebaranDesaMhs(Request $request) {
      $key = $request->key;

      if(empty($key) || $key == null){
        return response()->json(['message' => 'key param required.']);
      }
      try {
        if($key == 'mhs'){
          $data = DB::table('pmb_registration as a')
          ->join('pmb_registration_payment as b', 'b.registration_no', '=', 'a.registration_no')
          ->join('pmb_candidate as c', 'c.registration_no', '=', 'a.registration_no')
          ->join('pmb_provinsi as d', 'd.id', '=', 'c.prov_code')
          ->join('pmb_desa as e', 'e.id', '=', 'c.desa_code')
          ->join('pmb_kabupaten as f', 'f.id', '=', 'c.kabkot_code')
          ->join('pmb_kecamatan as h', 'h.id', '=', 'c.kec_code')
          ->join('siak_department as g', 'g.code', '=', 'a.department_code')
          ->select(
              'c.registration_no',
              'c.name as nama_mahasiswa',
              'c.student_code as npm',
              'c.sex',
              'g.name as prodi',
              'd.name as provinsi',
              'f.name as city',
              'h.name as kecamatan',
              'e.name as desa',
              'c.address'
          )
          ->whereRaw("b.fee_item IN ('1000', '1001', '1002', '1003')")
          ->where('a.academic_year', '2022/2023')
          ->where('c.student_code', '!=', '')
          ->get();
      
          $jsonData = json_decode(file_get_contents(storage_path('latlon/new_desa_latlong.json')), true);
          
          $newData = [];
          
          foreach ($data as $row) {
              $nameToMatch = $row->npm;
              $matchingData = array_filter($jsonData, function ($item) use ($nameToMatch) {
                  return $item['npm'] === $nameToMatch;
              });
          
              if (!empty($matchingData)) {
                  $matchingData = reset($matchingData);
                  $row->latitude = $matchingData['latitude'];
                  $row->longitude = $matchingData['longitude'];
              }
          
              $newData[] = (array) $row; 
          }
          
          return Datatables::of($newData)->addIndexColumn()->make(true);
        } else if($key == 'peminat') {
          $data = DB::table('pmb_registration as a')
          ->join('pmb_registration_payment as b', 'b.registration_no', '=', 'a.registration_no')
          ->join('pmb_candidate as c', 'c.registration_no', '=', 'a.registration_no')
          ->join('pmb_provinsi as d', 'd.id', '=', 'c.prov_code')
          ->join('pmb_desa as e', 'e.id', '=', 'c.desa_code')
          ->join('pmb_kabupaten as f', 'f.id', '=', 'c.kabkot_code')
          ->join('pmb_kecamatan as h', 'h.id', '=', 'c.kec_code')
          ->join('siak_department as g', 'g.code', '=', 'a.department_code')
          ->select(
              'c.registration_no',
              'c.name as nama_mahasiswa',
              'c.student_code as npm',
              'c.sex',
              'g.name as prodi',
              'd.name as provinsi',
              'f.name as city',
              'h.name as kecamatan',
              'e.name as desa',
              'c.address'
          )
          ->where(function ($query) {
              $query->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
                  ->where('a.academic_year', '2022/2023')
                  ->where('c.student_code', '');
          })
          ->get();
                
          $jsonData = json_decode(file_get_contents(storage_path('latlon/new_desa_latlong.json')), true);
          
          $newData = [];
          
          foreach ($data as $row) {
              $nameToMatch = $row->npm;
              $matchingData = array_filter($jsonData, function ($item) use ($nameToMatch) {
                  return $item['npm'] === $nameToMatch;
              });
          
              if (!empty($matchingData)) {
                  $matchingData = reset($matchingData);
                  $row->latitude = $matchingData['latitude'];
                  $row->longitude = $matchingData['longitude'];
              }
          
              $newData[] = (array) $row; 
          }
          
          return Datatables::of($newData)->addIndexColumn()->make(true);
        } else {
          return response()->json(['message' => 'value key param not allowed. must be [new, old]' ]);
        }

      } catch (\Exception $e) {
          return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function petaSebaranAsalSekolah(Request $request){
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
        if($key == 'new'){
          $data = Pmb_Registration::select('pmb_registration.registration_no', 'pmb_registration.candidate_name', 'pmb_candidate.sex', 'pmb_education_slta.nama_sekolah', 'pmb_education_slta.address_sekolah')
            ->join('pmb_candidate', 'pmb_candidate.registration_no', '=', 'pmb_registration.registration_no')
            ->join('pmb_education_slta', 'pmb_education_slta.registration_no', '=', 'pmb_candidate.registration_no')
            ->join('siak_department', 'siak_department.code', '=', 'pmb_registration.department_code')
            ->where('pmb_registration.academic_year', $tahunAkademik)
            ->where('pmb_registration.semester', $semester)
            ->get();

            $jsonData = json_decode(file_get_contents(storage_path('latlon/asal_sekolah_latlong.json')), true);

            $newData = [];

            foreach ($data as $row) {
                $addressToMatch = $row->address_sekolah;
                $matchingData = array_filter($jsonData, function ($item) use ($addressToMatch) {
                    return $item['name'] === $addressToMatch;
                });

                if (!empty($matchingData)) {
                    $matchingData = reset($matchingData);
                    $row->latitude = $matchingData['latitude'];
                    $row->longitude = $matchingData['longitude'];
                }

                $newData[] = [
                    'registration_no' => $row->registration_no,
                    'candidate_name' => $row->candidate_name,
                    'sex' => $row->sex,
                    'nama_sekolah' => $row->nama_sekolah,
                    'address_sekolah' => $row->address_sekolah,
                    'latitude' => $row->latitude ?? '', 
                    'longitude' => $row->longitude ?? '', 
                ];
              }

            return Datatables::of($newData)->addIndexColumn()->make(true);

        } else if($key == 'old') {
          $data = Pmb_Registration::select('pmb_registration.registration_no', 'pmb_registration.candidate_name', 'pmb_candidate.sex', 'pmb_education_slta.nama_sekolah', 'pmb_education_slta.address_sekolah')
          ->join('pmb_candidate', 'pmb_candidate.registration_no', '=', 'pmb_registration.registration_no')
          ->join('pmb_education_slta', 'pmb_education_slta.registration_no', '=', 'pmb_candidate.registration_no')
          ->join('siak_department', 'siak_department.code', '=', 'pmb_registration.department_code')
          ->where('pmb_registration.academic_year', $tahunAkademik)
          ->where('pmb_registration.semester', $semester)
          ->get();

          $jsonData = json_decode(file_get_contents(storage_path('latlon/asal_sekolah_latlong.json')), true);

          $oldData = [];

          foreach ($data as $row) {
              $addressToMatch = $row->address_sekolah;
              $matchingData = array_filter($jsonData, function ($item) use ($addressToMatch) {
                  return $item['name'] === $addressToMatch;
              });

              if (!empty($matchingData)) {
                  $matchingData = reset($matchingData);
                  $row->latitude = $matchingData['latitude'];
                  $row->longitude = $matchingData['longitude'];
              }

              $oldData[] = [
                  'registration_no' => $row->registration_no,
                  'candidate_name' => $row->candidate_name,
                  'sex' => $row->sex,
                  'nama_sekolah' => $row->nama_sekolah,
                  'address_sekolah' => $row->address_sekolah,
                  'latitude' => $row->latitude ?? '', 
                  'longitude' => $row->longitude ?? '', 
              ];
            }

          return Datatables::of($oldData)->addIndexColumn()->make(true);
        } else {
          return response()->json(['message' => 'value key param not allowed. must be [new, old]' ]);
        }

      } catch (\Exception $e) {
          return response()->json(['error' => $e->getMessage()], 500);
      }
    }
    











    // =======================================================================
    // =======================================================================
  

    // FUNGSI DI BAWAH UNTUK MENDAPATKAN DATA LATLONG DESA
    public function latlongDesa(Request $request){  
      try {
        $data = DB::table('pmb_registration as a')
        ->join('pmb_registration_payment as b', 'b.registration_no', '=', 'a.registration_no')
        ->join('pmb_candidate as c', 'c.registration_no', '=', 'a.registration_no')
        ->join('pmb_provinsi as d', 'd.id', '=', 'c.prov_code')
        ->join('pmb_desa as e', 'e.id', '=', 'c.desa_code')
        ->join('pmb_kabupaten as f', 'f.id', '=', 'c.kabkot_code')
        ->join('pmb_kecamatan as h', 'h.id', '=', 'c.kec_code')
        ->join('siak_department as g', 'g.code', '=', 'a.department_code')
        ->select(
            'c.registration_no',
            'c.name as nama_mahasiswa',
            'c.student_code as npm',
            'c.sex',
            'g.name as prodi',
            'd.name as provinsi',
            'f.name as city',
            'h.name as kecamatan',
            'e.name as desa',
            'c.address'
        )
        ->whereRaw("b.fee_item IN ('1000', '1001', '1002', '1003')")
        ->where('a.academic_year', '2022/2023')
        ->where('c.student_code', '!=', '')
        ->get();
  
  
          foreach ($data as $item) {

                  $village = $item->desa;
                  $subdistrict = $item->kecamatan;
                  $city = $item->city;
                  $province = $item->provinsi;

                  $address = "$village, $subdistrict, $city, $province";
                  $existingData = $this->getExistingData($item->npm);
  
                  if (!$existingData) {
                      $geocodedData = $this->geocodeAddressTest($address);
  
                      if ($geocodedData) {
                          $item->latitude = $geocodedData['latitude'];
                          $item->longitude = $geocodedData['longitude'];
                          $this->updateLatLongInDatabase($item->npm, $geocodedData['latitude'], $geocodedData['longitude']);
                      }
                  }
          }
  
          return Datatables::of($data)->addIndexColumn()->make(true);
      } catch (\Exception $e) {
          return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    private function getExistingData($npm) {
      $filePath = storage_path('latlon/new_desa_latlong.json');
      
      if (file_exists($filePath)) {
          $jsonData = file_get_contents($filePath);
          $data = json_decode($jsonData, true);

          if (is_array($data)) {
              foreach ($data as $item) {
                  // Check if the 'npm' matches the provided npm
                  if (isset($item['npm']) && $item['npm'] === $npm) {
                      return $item;
                  }
              }
          }
      }

      return null;
    }

    private function geocodeAddressTest($address) {
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
    
    private function updateLatLongInDatabase($npm, $latitude, $longitude) {
        $data = [];
    
        $filePath = storage_path('latlon/new_desa_latlong.json');
    
        if (file_exists($filePath)) {
            $jsonData = file_get_contents($filePath);
            $data = json_decode($jsonData, true);
        }
    
        $data[] = [
            'npm' => $npm,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    
        $jsonData = json_encode($data);
        file_put_contents($filePath, $jsonData);
    }    
  
}

