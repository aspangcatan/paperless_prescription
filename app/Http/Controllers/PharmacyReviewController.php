<?php

namespace App\Http\Controllers;

use App\Models\ConcurrentTherapy;
use App\Models\ErrorLog;
use App\Models\PharmacyReview;
use App\Models\PrescribedMedicine;
use App\Models\PrescriptionLog;
use App\Models\RenderedPrescription;
use App\Models\ReturnedPrescription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                ->skip($request->page * 10)
                ->take(11)
                ->get(['p.*', 'h.hospital_id', 'h.name', 'h.birthdate', 'h.sex', 'n.created_at as date_forwarded']);
            return response(['code' => 200, 'results' => $prescriptions]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function renderPrescription(Request $request)
    {
        try {
            $prescription = PharmacyReview::where('prescription_id', $request->id)->first();
            if (!$prescription) {
                return response(['code' => 500, 'message' => 'Prescription dont exists']);
            }

            #UPDATE MEDICATINS QUANTITY RENDERED
            $is_available = true;
            $medications = $request->medications;
            for ($i = 0; $i < count($medications); $i++) {
                $medication = PrescribedMedicine::find($medications[$i]["id"]);
                $medication->quantity_rendered = $medications[$i]["quantity_rendered"];
                $medication->date_rendered = Carbon::now();
                $medication->save();

                if ($request->type != '1') {
                    if ($medications[$i]["quantity_rendered"] < 1) {
                        $is_available = false;
                        #CANCEL CONCURRENT THERAPY
                        $current_concurrent = ConcurrentTherapy::where('prescription_id', $request->id)
                            ->whereNotNull(['approved_by']);
                        $current_concurrent->forceDelete();

                        if ($request->type == "4") {
                            //COMPUTE STOP DATE VIA START DATE AND DAYS APPROVED
                            $switched_concurrent = ConcurrentTherapy::where('for_switch', $request->id)->first();

                            $date = Carbon::parse($switched_concurrent->start_date);
                            $date = $date->addDays($switched_concurrent->days_approved - 1);
                            $stop_date = date('Y-m-d', strtotime($date));
                            $switched_concurrent->stop_date = $stop_date;
                            $switched_concurrent->save();
                        }
                    }
                }
            }

            $released_to = "NURSE";
            $remarks = "<span class='badge bg-success text-white'>Approved <i class='bi bi-hand-thumbs-up-fill'></i></span><span class='ms-2'>" . $request->remarks . "</span>";
            $rendered_prescription = new RenderedPrescription;
            $rendered_prescription->prescription_id = $request->id;
            $rendered_prescription->save();
            /*
            #If not available return to doctor
            #@TODO: UNCOMMENT IS AVAILABLE IF RENDER AMS NO
            if ($is_available) {
                #INSERT RECORD TO RENDERED PRESCRIPTION
                $rendered_prescription = new RenderedPrescription;
                $rendered_prescription->prescription_id = $request->id;
                $rendered_prescription->save();
            } else {
                $released_to = "DOCTOR";
                $remarks = "<span class='badge bg-danger text-white'>Rejected <i class='bi bi-hand-thumbs-down-fill'></i></span><span class='ms-2'>" . $request->remarks . "</span>";
                #INSERT RECORD TO RETURN PRESCRIPTION
                $returned = new ReturnedPrescription;
                $returned->prescription_id = $request->id;
                $returned->save();
            }
            */
            $logs = new PrescriptionLog;
            $logs->prescription_id = $request->id;
            $logs->origin = $request->origin;
            $logs->released_by = Auth::id();
            $logs->released_to = $released_to;
            $logs->remarks = $remarks;
            $logs->save();

            #REMOVE RECORD FROM PHARMACY REVIEWS
            $prescription->forceDelete();
            return response(['code' => 200, 'message' => 'Prescription rendered']);
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
