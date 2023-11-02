<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Siak_Departemen;
use Yajra\Datatables\Datatables;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KeuanganController extends Controller
{
  public function tunggakanMhs(){
    try {
      $data = Siak_Departemen::select('siak_department.name as prodi', \DB::raw('COUNT(siak_student.code) as jml_mhs'), \DB::raw('SUM(siak_fee_payment.nominal) as total_piutang'))
        ->join('siak_student', 'siak_department.code', '=', 'siak_student.department_code')
        ->join('siak_fee_payment', 'siak_fee_payment.student_code', '=', 'siak_student.code')
        ->where('siak_fee_payment.academic_year', '2023/2024')
        ->where('siak_fee_payment.semester', 'GASAL')
        ->where('siak_fee_payment.paid', 'N')
        ->groupBy('siak_department.code', 'siak_department.name')
        ->get();

      return DataTables::of($data)->addIndexColumn()->make(true);

    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }

  public function tunggakanMhsSpp(){
    try {
      $data = Siak_Departemen::select('siak_department.name AS prodi', DB::raw('COUNT(siak_student.code) AS jml_mhs'), DB::raw('SUM(siak_fee_payment.nominal) AS total_piutang'))
        ->join('siak_student', 'siak_student.department_code', '=', 'siak_department.code')
        ->join('siak_fee_payment', 'siak_fee_payment.student_code', '=', 'siak_student.code')
        ->where('siak_fee_payment.academic_year', '2023/2024')
        ->where('siak_fee_payment.semester', 'GASAL')
        ->where('siak_fee_payment.paid', 'N')
        ->where('siak_fee_payment.fee_item', '1020')
        ->groupBy('siak_department.code', 'siak_department.name') // Tambahkan siak_department.name di sini
        ->get();

      return DataTables::of($data)->addIndexColumn()->make(true);

    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }

  public function tunggakanMhsSksUjian(){
    try {
      $data = Siak_Departemen::select('siak_department.name AS prodi', DB::raw('COUNT(siak_student.code) AS jml_mhs'), DB::raw('SUM(siak_fee_payment.nominal) AS total_piutang'))
        ->join('siak_student', 'siak_student.department_code', '=', 'siak_department.code')
        ->join('siak_fee_payment', 'siak_fee_payment.student_code', '=', 'siak_student.code')
        ->where('siak_fee_payment.academic_year', '2023/2024')
        ->where('siak_fee_payment.semester', 'GASAL')
        ->where('siak_fee_payment.paid', 'N')
        ->whereIn('siak_fee_payment.fee_item', ['1200', '1210'])
        ->groupBy('siak_department.code', 'siak_department.name')
        ->get();


      return DataTables::of($data)->addIndexColumn()->make(true);

    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }
  
}