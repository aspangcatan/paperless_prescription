<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use App\Models\NurseReview;
use App\Models\PharmacyReview;
use App\Models\PrescribedMedicine;
use App\Models\PrescriptionLog;
use App\Models\RenderedPrescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PharmacyReviewController extends Controller
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

            $prescriptions = DB::table('paperless_prescription.pharmacy_reviews as n')
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

    public function renderPrescription(Request $request)
    {
        try {
            $prescription = PharmacyReview::where('prescription_id', $request->prescription_id)->first();
            if (!$prescription) {
                return response(['code' => 500, 'message' => 'Prescription dont exists']);
            }

            #UPDATE MEDICATINS QUANTITY RENDERED
            $medications = $request->medications;
            for ($i = 0; $i < count($medications); $i++) {
                $medication = PrescribedMedicine::find($medications[$i]["id"]);
                $medication->quantity_returned = $medications[$i]["quantity_rendered"];
                $medication->save();
            }

            $logs = new PrescriptionLog;
            $logs->prescription_id = $request->prescription_id;
            $logs->origin = "PHARMACY";
            $logs->released_by = 1116;
            $logs->released_to = "NURSE";
            $logs->remarks = $request->remarks;
            $logs->save();

            #INSERT RECORD TO RENDERED PRESCRIPTION
            $rendered_prescription = new RenderedPrescription;
            $rendered_prescription->prescription_id = $request->prescription_id;
            $rendered_prescription->save();

            #REMOVE RECORD FROM PHARMACY REVIEWS
            $prescription->forceDelete();
            return response(['code' => 200, 'message' => 'Prescription rendered']);
        } catch (\Exception $exception) {
            self::insertErrorLogs(1116, 'renderPrescription', $request, $exception->getMessage());
            return response(['code' => 200, 'message' => "Something went wrong"]);
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
