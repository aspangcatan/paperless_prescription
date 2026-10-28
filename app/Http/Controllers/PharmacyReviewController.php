<?php

namespace App\Http\Controllers;

use App\Models\ConcurrentTherapy;
use App\Models\ErrorLog;
use App\Models\NurseReview;
use App\Models\PharmacyReview;
use App\Models\PrescribedMedicine;
use App\Models\PrescriptionLog;
use App\Models\RenderedPrescription;
use App\Models\ReturnedPrescription;
use App\Services\PrescriptionWorkflow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
                ->skip($request->page * 10)
                ->take(11)
                ->orderBy('n.created_at','DESC')
                ->get(['p.*', 'h.hospital_id', 'h.name', 'h.birthdate', 'h.sex', 'n.created_at as date_forwarded']);
            return response(['code' => 200, 'results' => $prescriptions]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    /**
     * Dispenses the approved medicines that haven't been dispensed yet, all of them at once.
     * Pending, rejected and already-dispensed medicines can't be touched from here.
     */
    public function renderPrescription(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
                'remarks' => 'nullable|string|max:2000',
                'medications' => 'array',
                'medications.*.id' => 'required|integer|distinct',
                'medications.*.quantity_rendered' => 'required|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response(['code' => 500, 'message' => $validator->errors()->first()]);
            }

            $result = DB::transaction(function () use ($request) {
                $prescription = PharmacyReview::where('prescription_id', $request->id)->lockForUpdate()->first();
                if (!$prescription) {
                    return ['code' => 500, 'message' => 'Prescription dont exists'];
                }

                $to_dispense = PrescriptionController::medicinesInStep("PHARMACY", $request->id)->keyBy('id');
                $submitted = collect($request->medications ?? [])->keyBy(function ($medication) {
                    return (int)$medication['id'];
                });

                if ($submitted->keys()->sort()->values()->all() !== $to_dispense->keys()->sort()->values()->all()) {
                    return ['code' => 500, 'message' => 'The medicines to dispense have changed. Please refresh and try again.'];
                }

                #IF UNAVAILABLE PROMPT ERROR, BEFORE ANYTHING IS SAVED
                foreach ($submitted as $medication) {
                    if ($medication["quantity_rendered"] < 1) {
                        return ['code' => 500, 'message' => 'Cannot render if not available'];
                    }
                }

                #UPDATE MEDICATINS QUANTITY RENDERED
                foreach ($submitted as $id => $medication) {
                    $medicine = $to_dispense->get($id);
                    $medicine->quantity_rendered = $medication["quantity_rendered"];
                    $medicine->date_rendered = Carbon::now();
                    $medicine->save();
                }

                NurseReview::firstOrCreate(['prescription_id' => $request->id]);
                PrescriptionWorkflow::log($request->id, "PHARMACY", "NURSE", $request->remarks, 'approved');

                #REMOVE RECORD FROM PHARMACY REVIEWS
                $prescription->forceDelete();
                return ['code' => 200, 'message' => 'Prescription Tagged'];
            });

            return response($result);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'renderPrescription', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => "Something went wrong"]);
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
