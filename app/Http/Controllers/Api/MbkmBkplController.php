<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Bkpl_Event;
use App\Models\BkplPrograms;
use Yajra\Datatables\Datatables;

use Illuminate\Http\Request;

class MbkmBkplController extends Controller
{
  public function pelaksanaan(){
    try {
      $data = Bkpl_Event::select('bkpl_events.academic_year', 'bkpl_events.semester', 'siak_department.name AS prodi', 'bkpl_events.program', 'bp1.name', 'bkpl_events.event_name')
      ->join('bkpl_programs as bp1', 'bp1.code', '=', 'bkpl_events.program')
      ->join('bkpl_participants', 'bkpl_participants.event_id', '=', 'bkpl_events.id')
      ->join('siak_student', 'siak_student.code', '=', 'bkpl_participants.student_code')
      ->join('siak_department', 'siak_department.code', '=', 'siak_student.department_code')
      ->groupBy('bkpl_events.academic_year', 'bkpl_events.semester', 'siak_department.name', 'bkpl_events.program', 'bp1.name', 'bkpl_events.event_name')
      ->get();

      return Datatables::of($data)->addIndexColumn()->make(true);
    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }
}


