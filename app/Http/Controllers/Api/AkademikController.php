<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pmb_Candidate;
use App\Models\Pmb_Provinsi;
use App\Models\Pmb_Registration;
use App\Models\Siak_Student;
use App\Models\Siak_Student_Snapshot;
use Yajra\Datatables\Datatables;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AkademikController extends Controller
{
    public function mhsDaftar(Request $request){
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

    public function mhsDiterima(Request $request){
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

    public function petaSebaranNewMhs(){
      try {
        $data = DB::table('pmb_registration as a')
          ->select('a.*', 'e.name as prodi', 'd.name', 'c.address', 'd.latitude', 'd.longitude')
          ->join('pmb_registration_payment as b', 'b.registration_no', '=', 'a.registration_no')
          ->join('pmb_candidate as c', 'c.registration_no', '=', 'a.registration_no')
          ->join('pmb_desa as d', 'd.id', '=', 'c.desa_code')
          ->join('siak_department as e', 'e.code', '=', 'a.department_code')
          ->where('b.paid', 'Y')
          ->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
          ->where('a.academic_year', '2023/2024')
          ->where('a.semester', 'GASAL')
          ->get();

          return Datatables::of($data)->addIndexColumn()->make(true);

      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }
    public function petaSebaranOldMhs(){
      try {
        $data = Siak_Student::select('siak_student.code', 'siak_student.name', 'pmb_candidate.sex', 'siak_student.address', 'siak_student.city', 'pmb_desa.name AS desa', 'siak_department.name AS prodi', 'siak_student.status', 'pmb_desa.latitude', 'pmb_desa.longitude')
          ->join('pmb_candidate', 'pmb_candidate.student_code', '=', 'siak_student.code')
          ->join('pmb_registration', 'pmb_registration.registration_no', '=', 'pmb_candidate.registration_no')
          ->join('pmb_desa', 'pmb_desa.id', '=', 'pmb_candidate.desa_code')
          ->join('siak_department', 'siak_department.code', '=', 'siak_student.department_code')
          ->where('pmb_registration.registration_no', '!=', '2023/2024')
          ->get();

          return Datatables::of($data)->addIndexColumn()->make(true);

      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }
}