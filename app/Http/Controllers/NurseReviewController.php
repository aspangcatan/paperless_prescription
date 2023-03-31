<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NurseReviewController extends Controller
{
    public function getPrescriptions(Request $request)
    {
        try {
            if (session("station") == "") {
                return response(['code' => 200, 'results' => [], 'another' => []]);
            }

            $search_filters = [];
            if ($request->filter_patient != "") {
                $search_filters[] = ['h.name', 'LIKE', "%" . $request->filter_patient . "%"];
            }

            if ($request->filter_date != "") {
                $search_filters[] = ['p.created_at', 'LIKE', "%" . $request->filter_date . "%"];
            }

            $prescriptions = DB::table('paperless_prescription.nurse_reviews as n')
                ->join('paperless_prescription.prescriptions as p', 'n.prescription_id', '=', 'p.id')
                ->join('paperless_prescription.patient_history as h', 'n.prescription_id', '=', 'h.prescription_id')
                ->where($search_filters)
                ->Where(function ($query) {
                    $station = session("station");
                    $station = explode(',', $station);
                    for ($i = 0; $i < count($station); $i++) {
                        if ($i == 0) {
                            $query->where('p.station', '=', $station[$i]);
                            continue;
                        }
                        $query->orWhere('p.station', '=', $station[$i]);
                    }
                })
                ->skip($request->page * 10)
                ->take(11)
                ->get(['p.*', 'h.hospital_id', 'h.name', 'h.birthdate', 'h.sex', 'n.created_at as date_forwarded']);
            return response(['code' => 200, 'results' => $prescriptions]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
}
