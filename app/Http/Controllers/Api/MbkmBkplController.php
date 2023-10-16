<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\BkplPrograms;
use Yajra\Datatables\Datatables;

use Illuminate\Http\Request;

class MbkmBkplController extends Controller
{
  public function pelaksanaan(){
    try {
      $data = BkplPrograms::select('bkpl_events.academic_year', 'bkpl_events.semester', 'bkpl_programs.name', \DB::raw('COUNT(bkpl_events.id) as total'))
      ->join('bkpl_events', 'bkpl_programs.code', '=', 'bkpl_events.program')
      ->groupBy('bkpl_events.academic_year', 'bkpl_events.semester', 'bkpl_programs.name')
      ->get();

      return Datatables::of($data)->addIndexColumn()->make(true);


  
    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }
}