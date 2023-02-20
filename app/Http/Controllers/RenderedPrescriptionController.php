<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RenderedPrescriptionController extends Controller
{
    public function getPrescriptions(Request $request)
    {
        try {
            $search_filters = [];
            if ($request->filter_patient != "") {
                $search_filters[] = ['h.name', 'LIKE', "%" . $request->filter_patient . "%"];
            }

            if ($request->filter_station != "") {
                $search_filters[] = ['p.station', 'LIKE', "%" . $request->filter_station . "%"];
            }

            if ($request->filter_date != "") {
                $search_filters[] = ['p.created_at', 'LIKE', "%" . $request->filter_date . "%"];
            }

            $prescriptions = DB::table('paperless_prescription.rendered_prescriptions as n')
                ->join('paperless_prescription.prescriptions as p', 'n.prescription_id', '=', 'p.id')
                ->join('paperless_prescription.patient_history as h', 'n.prescription_id', '=', 'h.prescription_id')
                ->where($search_filters)
                ->skip(0)
                ->take(10)
                ->get(['p.*', 'h.hospital_id', 'h.name', 'h.birthdate', 'h.sex','n.created_at as date_forwarded']);
            return response(['code' => 200, 'results' => $prescriptions]);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'returnMeds', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function insertErrorLogs($action_by, $action, $params, $details)
    {
        $log = new ErrorLog;
        $log->action_by = $action_by;
        $log->action = $action;
        $log->params = $params;
        $log->details = $details;
        $log->save();
    }
}
