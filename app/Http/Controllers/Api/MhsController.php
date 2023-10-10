<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Pmb_Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;




class MhsController extends Controller
{
    public function mhsDaftar(){
      $data = Pmb_Candidate::all();

      // $data = DB::table('pmb_registration AS a')
      //     ->select('e.name AS fakultas', 'd.name AS prodi', DB::raw('COUNT(a.registration_no) AS total'))
      //     ->join('pmb_registration_payment AS b', 'b.registration_no', '=', 'a.registration_no')
      //     ->join('pmb_candidate AS c', 'c.registration_no', '=', 'a.registration_no')
      //     ->join('siak_department AS d', 'd.code', '=', 'a.department_code')
      //     ->join('siak_faculty AS e', 'e.code', '=', 'd.faculty_code')
      //     ->where('b.paid', 'Y')
      //     ->whereIn('b.fee_item', ['1000', '1001', '1002', '1003'])
      //     ->where('a.academic_year', '2023/2024')
      //     ->where('a.semester', 'GASAL')
      //     ->groupBy('d.code')
      //     ->orderBy('e.code')
      //     ->orderBy('d.npm_code')
      //     ->get();

      return new ApiResource(true, 'Mahasiswa Terdaftar', $data);
    }
}
