<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Siak_Departemen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataFilterController extends Controller{
  public function dataProdi(){
    try {
      $departments = DB::select('select code, faculty_code from siak_department');
      return $departments;
    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }
  public function dataFakultas(){
    try {
      $faculty = DB::select("SELECT code, name FROM siak_faculty WHERE code NOT IN ('YPIKA', 'FPASCA')");
      return $faculty;
    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }
  public function dataSemester(){
    try {
      $semester = DB::select('SELECT *  FROM pmb_semester');
      return $semester;
    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }
}

