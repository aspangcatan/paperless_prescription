<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use App\Models\ReturnedMeds;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturnedMedsController extends Controller
{
    public function getReturnedMeds(Request $request)
    {
        try {
            $search_filters = [];

//            if ($request->filter_patient != "") {
//                $search_filters[] = ['h.name', 'LIKE', "%" . $request->filter_patient . "%"];
//            }
//
//            if ($request->filter_station != "") {
//                $search_filters[] = ['p.station', 'LIKE', "%" . $request->filter_station . "%"];
//            }
//
//            if ($request->filter_date != "") {
//                $search_filters[] = ['p.created_at', 'LIKE', "%" . $request->filter_date . "%"];
//            }

            $prescriptions = DB::table('paperless_prescription.returned_meds as r')
                ->select(DB::raw('r.id,h.hospital_id, h.name, p.station, pm.description, r.returned_amount, CONCAT(retu.lname," ",retu.fname) as returned_by, r.created_at as date_returned,
                CONCAT(recu.lname," ",recu.fname) as received_by, r.updated_at as date_received'))
                ->join('paperless_prescription.prescribed_medicines as pm', 'r.prescribed_meds_id', '=', 'pm.id')
                ->join('paperless_prescription.prescriptions as p', 'pm.prescription_id', '=', 'p.id')
                ->join('paperless_prescription.patient_history as h', 'pm.prescription_id', '=', 'h.prescription_id')
                ->join('tdh_user.users as retu', 'r.returned_by', '=', 'retu.id')
                ->leftJoin('tdh_user.users as recu', 'r.received_by', '=', 'recu.id')
                ->where($search_filters)
                ->skip(0)
                ->take(10)
                ->get();
            return response(['code' => 200, 'results' => $prescriptions]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function receiveMedicine(Request $request)
    {
        try {
            $returned_meds = ReturnedMeds::find($request->id);
            if (!$returned_meds) {
                return response(['code' => 500, 'message' => 'Returned meds id cannot be found']);
            }

            if ($returned_meds->received_by) {
                return response(['code' => 500, 'message' => 'Medicine already received']);
            }

            $returned_meds->received_by = Auth::id();
            $returned_meds->save();
            return response(['code' => 200, 'message' => 'Medicine tag as received']);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'receiveMedicine', $request, $exception->getMessage());
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
