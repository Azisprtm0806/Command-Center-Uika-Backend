<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\bkpl_program;
use Illuminate\Http\Request;


class MbkmBkplController extends Controller
{
  public function pelaksanaan(){
    try {
      $data = bkpl_program::select('bkpl_events.academic_year', 'bkpl_events.semester', 'bkpl_programs.name', \DB::raw('COUNT(bkpl_events.id) as total'))
      ->join('bkpl_events', 'bkpl_programs.code', '=', 'bkpl_events.program')
      ->groupBy('bkpl_events.academic_year', 'bkpl_events.semester', 'bkpl_programs.name')
      ->get();

        return new ApiResource(true, 'Jumlah pelaksanaan MBKM Per bidang Per Prodi', $data);
  
    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }
}