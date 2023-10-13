<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Siak_Departemen;
use Illuminate\Http\Request;


class KeuanganController extends Controller
{
  public function tunggakanMhsPerProdi(){
    try {
      $data = Siak_Departemen::select('siak_department.name as prodi', \DB::raw('COUNT(siak_student.code) as jml_mhs'), \DB::raw('SUM(siak_fee_payment.nominal) as total_piutang'))
        ->join('siak_student', 'siak_department.code', '=', 'siak_student.department_code')
        ->join('siak_fee_payment', 'siak_fee_payment.student_code', '=', 'siak_student.code')
        ->where('siak_fee_payment.academic_year', '2023/2024')
        ->where('siak_fee_payment.semester', 'GASAL')
        ->where('siak_fee_payment.paid', 'N')
        ->groupBy('siak_department.code', 'siak_department.name')
        ->get();

        return new ApiResource(true, 'Jumlah Tunggakan Mahasiswa Per Prodi', $data);
  
    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }


  
}