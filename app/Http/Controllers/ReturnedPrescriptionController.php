<?php

namespace App\Http\Controllers;

use App\Models\NurseReview;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use App\Models\PrescriptionLog;
use App\Models\ReturnedPrescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnedPrescriptionController extends Controller
{
    public function getPrescriptions(Request $request)
    {
        try {
            $search_filters = [['p.physician_id', '=', 1116]];

            if ($request->filter_patient != "") {
                $search_filters[] = ['h.name', 'LIKE', "%" . $request->filter_patient . "%"];
            }

            if ($request->filter_station != "") {
                $search_filters[] = ['p.station', 'LIKE', "%" . $request->filter_station . "%"];
            }

            if ($request->filter_date != "") {
                $search_filters[] = ['p.created_at', 'LIKE', "%" . $request->filter_date . "%"];
            }

            $prescriptions = DB::table('paperless_prescription.returned_prescriptions as n')
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

    public function updatePrescription(Request $request)
    {
        try {
            #UPDATE URGENCY
            $prescription = Prescription::find($request->prescription_id);
            if (!$prescription) {
                return response(['code' => 500, 'message' => 'Prescription dont exists']);
            }
            $prescription->urgency = $request->urgency;
            $prescription->save();

            #INSERT ALL MEDICATIONS AS BULK
            #DELETE ALL MEDICATIONS

            $delete_medications = PrescribedMedicine::where('prescription_id', $request->prescription_id);
            $delete_medications->forceDelete();

            $medications = $request->medications;
            for ($i = 0; $i < count($medications); $i++) {
                $medications[$i]['prescription_id'] = $request->prescription_id;
            }
            PrescribedMedicine::insert($medications);

            #INSERT LOGS IF REGULAR PRESCRIPTION
            $logs = new PrescriptionLog;
            $logs->prescription_id = $request->prescription_id;
            $logs->origin = "DOCTOR";
            $logs->released_by = 1116;
            $logs->released_to = "NURSE";
            $logs->remarks = $request->remarks;
            $logs->save();

            $nurse = new NurseReview();
            $nurse->prescription_id = $request->prescription_id;
            $nurse->save();

            $returned_prescription = ReturnedPrescription::where('prescription_id',$request->prescription_id)->first();
            $returned_prescription->forceDelete();
            return response(['code' => 200, 'message' => 'Prescription updated']);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
}
