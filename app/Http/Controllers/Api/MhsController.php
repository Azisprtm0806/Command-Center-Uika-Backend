<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Pmb_Candidate;
use App\Models\Pmb_Provinsi;
use App\Models\Pmb_Registration;
use App\Models\Siak_Student;
use Illuminate\Http\Request;


class MhsController extends Controller
{
    public function mhsDaftar(){
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
      

          return new ApiResource(true, 'Mahasiswa Daftar', $data);
          
        }catch (\Exception $e) {
          return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function mhsDiterima(){
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
        

            return new ApiResource(true, 'Mahasiswa diterima', $data);
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
    

          return new ApiResource(true, 'Mahasiswa Per Provinsi', $data);
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
    

          return new ApiResource(true, 'Mahasiswa Per Provinsi', $data);
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

          return new ApiResource(true, 'Mahasiswa Aktif yang Belum FRS', $data);

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

          return new ApiResource(true, 'Mahasiswa yang sudah bayar SPP', $data);

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

        return new ApiResource(true, 'Mahasiswa yang belum bayar SPP', $data);

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

        return new ApiResource(true, 'Mahasiswa penerima beasiswa', $data);

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

          return new ApiResource(true, 'Peta Sebaran Asal Mahasiswa', $data);
      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
      }
    }
}