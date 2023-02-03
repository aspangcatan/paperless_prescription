<?php

namespace App\Http\Controllers;

use App\Models\NurseReview;
use App\Models\PharmacyReview;
use App\Models\Prescription;
use App\Models\PrescriptionLog;
use App\Models\ReturnedPrescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NurseReviewController extends Controller
{
    public function getPrescriptions(Request $request)
    {
        try {
            $station = "INTERNAL MEDICINE";

            $search_filters = [];
            if ($request->filter_patient != "") {
                $search_filters[] = ['h.name', 'LIKE', "%" . $request->filter_patient . "%"];
            }

            if ($request->filter_date != "") {
                $search_filters[] = ['p.created_at', 'LIKE', "%" . $request->filter_date . "%"];
            }

            if (1 == 1) {
                $search_filters[] = ['p.station', '=', $station];
            }

            $prescriptions = DB::table('paperless_prescription.nurse_reviews as n')
                ->join('paperless_prescription.prescriptions as p', 'n.prescription_id', '=', 'p.id')
                ->join('paperless_prescription.patient_history as h', 'n.prescription_id', '=', 'h.prescription_id')
                ->where($search_filters)
                ->skip(0)
                ->take(10)
                ->get(['p.id', 'h.hospital_id', 'h.name', 'h.birthdate', 'h.sex', 'p.station', 'p.ward', 'p.bed_no', 'p.urgency', 'p.created_at']);
            return response(['code' => 200, 'results' => $prescriptions]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
}
