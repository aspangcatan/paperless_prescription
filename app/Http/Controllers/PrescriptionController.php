<?php

namespace App\Http\Controllers;

use App\Models\ConcurrentTherapy;
use App\Models\CultureSent;
use App\Models\DysfunctionCriteria;
use App\Models\ErrorLog;
use App\Models\FocusInfection;
use App\Models\IccReview;
use App\Models\Immunocompromising;
use App\Models\IndicationUse;
use App\Models\IndicationUseDetails;
use App\Models\InfectiousDiagnosis;
use App\Models\MicrobiologicalResult;
use App\Models\NurseReview;
use App\Models\PatientHistory;
use App\Models\PharmacyReview;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use App\Models\PrescriptionLog;
use App\Models\ReturnedPrescription;
use App\Models\SepsisCriteria;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    public function addDays()
    {
        $date = Carbon::now();
        $daysToAdd = 4;
        $date = $date->addDays($daysToAdd);
        return date('Y-m-d', strtotime($date));
    }


    public function getPrescription(Request $request)
    {
        try {
            $search_filters = [];
            if ($request->filter_patient != "") {
                $search_filters[] = ['h.name', 'LIKE', "%" . $request->filter_patient . "%"];
            }

            if ($request->filter_date != "") {
                $search_filters[] = ['p.created_at', 'LIKE', "%" . $request->filter_date . "%"];
            }

            $prescriptions = DB::table('prescriptions as p')
                ->join('patient_history as h', 'p.id', '=', 'h.prescription_id')
                ->where($search_filters)
                ->where(function ($q) {
                    $q->where('p.physician_id', '=', Auth::id())
                        ->orWhere('p.pgi_id', '=', Auth::id());
                })
                ->skip(0)
                ->take(10)
                ->get(['p.*', 'h.hospital_id', 'h.name', 'h.birthdate', 'h.sex']);
            return response(['code' => 200, 'results' => $prescriptions]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function getPrescriptionDetails($id)
    {
        $ams = (object)[];
        try {
            $prescription = Prescription::find($id);
            if ($prescription->type == '2') {
                #GET AMS
                $indication = IndicationUse::where('prescription_id', $id)->first();
                $microbiologicals = MicrobiologicalResult::where('prescription_id', $id)->get();

                $ams = ['indication_use' => $indication, 'microbiologicals' => $microbiologicals];
            }
            #GET MEDICATIONS
            $medications = PrescribedMedicine::where('prescription_id', $id)->get();
            return response(['code' => 200, 'results' => $medications, 'ams' => $ams]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function getConcurrentTherapy(Request $request)
    {
        try {
            $concurrent = ConcurrentTherapy::where('hospital_id', $request->hospital_id)->get();
            return response(['code' => 200, 'results' => $concurrent]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function getPrescriptionLogs($id)
    {
        try {
            #GET MEDICATIONS
            $logs = DB::table('paperless_prescription.prescription_logs as l')
                ->join('tdh_user.users as u', 'l.released_by', '=', 'u.id')
                ->where('l.prescription_id', '=', $id)
                ->get(['l.origin', 'u.fname', 'u.mname', 'u.lname', 'l.released_to', 'l.remarks', 'l.date_forwarded']);
            return response(['code' => 200, 'results' => $logs]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function insertPrescription(Request $request)
    {
        $prescription_id = 0;
        try {
            #PATIENT REQUEST DATA OBJECT
            $patient = $request->patient;
            $prescription = new Prescription;
            $prescription->hospital_id = $patient['hospital_id'];
            $prescription->physician_id = Auth::id();
            $prescription->pgi_id = null;
            $prescription->station = $patient['station'];
            $prescription->ward = $patient['ward'];
            $prescription->bed_no = $patient['bed_no'];
            $prescription->urgency = $patient['urgency'];
            $prescription->type = $patient['type'];
            #INSERT AMS ADDITIONAL INFORMATION
            if ($patient['type'] == "2") {
                $prescription->drug_allergy = $patient['drug_allergy'];
                $prescription->weight = $patient['weight'];
                $prescription->height = $patient['height'];
                $prescription->serum_creatinine = $patient['serum_creatinine'];
                $prescription->creatinine_clearance = $patient['creatinine_clearance'];
                $prescription->transaminases = $patient['transaminases'];
                $prescription->sgpt = $patient['sgpt'];
                $prescription->sgot = $patient['sgot'];
            }
            $prescription->save();
            $prescription_id = $prescription->id;

            if ($patient['type'] == "2") {
                #THIS IS SEPARATED BECAUSE WE NEED THE PRESCRIPTION ID
                #INSERT INDICATION USE

                $indication_use_param = $request->indication_use;
                $indication = new IndicationUse;
                $indication->prescription_id = $prescription_id;
                $indication->description = $indication_use_param['description'];
                $indication->duration_requested = $indication_use_param['duration_requested'];
                $indication->reason = $indication_use_param['reason'];
                $indication->save();

                #INSERT MICROBIOLOGICAL
                $microbiologicals = $request->microbiologicals;
                for ($i = 0; $i < count($microbiologicals); $i++) {
                    $microbiologicals[$i]['prescription_id'] = $prescription_id;
                }
                MicrobiologicalResult::insert($microbiologicals);

                $concurrent_therapy = $request->concurrent_therapy;
                for ($i = 0; $i < count($concurrent_therapy); $i++) {
                    $concurrent_therapy[$i]['prescription_id'] = $prescription_id;
                }
                ConcurrentTherapy::insert($concurrent_therapy);
            }

            #INSERT PATIENT HISTORY
            $patient_history = new PatientHistory;
            $patient_history->prescription_id = $prescription_id;
            $patient_history->hospital_id = $patient['hospital_id'];
            $patient_history->name = $patient['name'];
            $patient_history->birthdate = $patient['birthdate'];
            $patient_history->sex = $patient['sex'];
            $patient_history->save();

            #INSERT ALL MEDICATIONS AS BULK
            $medications = $request->medications;
            for ($i = 0; $i < count($medications); $i++) {
                $medications[$i]['prescription_id'] = $prescription_id;
            }
            PrescribedMedicine::insert($medications);

            $released_to = "NURSE";
            if ($patient['type'] == "2") {
                $released_to = "IPCC";
                $icc = new IccReview();
                $icc->prescription_id = $prescription_id;
                $icc->save();
                /*
                #TODO: CHECK IF PRESCRIBED MEDICINE IS ALREADY APPROVED FOR CERTAIN DAYS
                #IF ALREADY APPROVED, AUTOMATIC SEND TO NURSE REVIEW
                #GET LATEST PRESCRIPTION BASED ON MEDICATION AND PATIENT AND GET DAYS APPROVED COMPARE TO CURRENT DAY IF WITHIN AND DEFINITIVE CASE
                #$medications[0] is only one
                $latest_approved_prescription = DB::table('paperless_prescription.prescriptions as p')
                    ->join('paperless_prescription.indication_use as u', 'p.id', '=', 'u.prescription_id')
                    ->join('paperless_prescription.prescribed_medicines as m', 'p.id', '=', 'm.prescription_id')
                    ->where('p.hospital_id', '=', $patient['hospital_id'])
                    ->where('u.description', '=', $indication_use_param['description'])
                    ->where('m.description', '=', $medications[0]['description'])
                    ->whereNotNull(['p.date_approved'])
                    ->orderBy('p.created_at', 'DESC')
                    ->get(['p.id', 'p.created_at', 'p.days_approved'])
                    ->first();

                if (!$latest_approved_prescription) {
                    $released_to = "IPCC";
                    $icc = new IccReview();
                    $icc->prescription_id = $prescription_id;
                    $icc->save();
                } else {
                    #CALCULATE DAYS PASSED SINCE THE APPROVAL
                    $earlier = new DateTime($latest_approved_prescription->created_at);
                    $later = Carbon::now();

                    #CHECK IF THE NO. OF DAYS APPROVED ARE BEYOND TODAYS TODATE
                    #IF BEYOND, PROCEED TO IPCC FOR ANOTHER APPROVAL
                    if ($later->diff($earlier)->format("%a") + 1 > $latest_approved_prescription->days_approved) {
                        #PROCEED TO ICC
                        $released_to = "IPCC";
                        $icc = new IccReview();
                        $icc->prescription_id = $prescription_id;
                        $icc->save();
                    } else {
                        $prescription->latest_approved_id = $latest_approved_prescription->id;
                        $prescription->save();
                        $nurse = new NurseReview();
                        $nurse->prescription_id = $prescription_id;
                        $nurse->save();
                    }
                }
                */
            } else {
                $nurse = new NurseReview();
                $nurse->prescription_id = $prescription_id;
                $nurse->save();
            }

            #INSERT LOGS IF REGULAR PRESCRIPTION
            $logs = new PrescriptionLog;
            $logs->prescription_id = $prescription_id;
            $logs->origin = "DOCTOR";
            $logs->released_by = Auth::id();
            $logs->released_to = $released_to;
            $logs->remarks = $patient['remarks'];
            $logs->save();

            return response(['code' => 200, 'message' => 'Prescribe successfully']);
        } catch (\Exception $exception) {
            self::delete($prescription_id);
            self::insertErrorLogs(Auth::id(), 'insertPrescription', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => "Something went wrong"]);
        }
    }

    public function approvePrescription(Request $request)
    {
        try {
            if ($request->origin == "NURSE")
                $prescription = NurseReview::where('prescription_id', $request->id);
            else if ($request->origin == "ICC") {
                $prescription = IccReview::where('prescription_id', $request->id);
            } else {
                $prescription = PharmacyReview::where('prescription_id', $request->id);
            }

            if (!$prescription) {
                return response(['code' => 500, 'message' => 'Prescription dont exists']);
            }

            $date_approved = Carbon::now();
            $released_to = "PHARMACY";
            if ($request->origin == "ICC") {
                #GET MEDICATIONS
                #INSERT CONCURRENT ANTI MICROBIAL THERAPY
                #CALCULATE STOP DATE BASED ON THE DAYS APPROVED AND DATE APPROVED
                $date = Carbon::now();
                $date = $date->addDays($request->days_approved - 1);
                $stop_date = date('Y-m-d', strtotime($date));

                $medications = PrescribedMedicine::where('prescription_id', $request->id)
                    ->get(['description', 'dosage_strength', 'dosage_unit', 'frequency']);


                for ($i = 0; $i < count($medications); $i++) {
                    $concurrent = new ConcurrentTherapy;
                    $concurrent->prescription_id = $request->id;
                    $concurrent->hospital_id = $request->hospital_id;
                    $concurrent->concurrent_date = $date_approved;
                    $concurrent->antimicrobial = $medications[$i]->description;
                    $concurrent->dosing_regimen = $medications[$i]->dosage_strength . '' . $medications[$i]->dosage_unit . ' ' . $medications[$i]->frequency;
                    $concurrent->start_date = $date_approved;
                    $concurrent->stop_date = $stop_date;
                    $concurrent->save();
                }

                #UPDATE DAYS APPROVED
                $doctor = Prescription::find($request->id);
                $doctor->days_approved = $request->days_approved;
                $doctor->approved_by = Auth::id();
                $doctor->date_approved = $date_approved;
                $doctor->save();
                #FORWARD TO PHARMACY
                $nurse = new NurseReview();
                $nurse->prescription_id = $request->id;
                $nurse->save();
                $released_to = "NURSE";
            } else {
                #FORWARD TO PHARMACY
                $pharmacy = new PharmacyReview;
                $pharmacy->prescription_id = $request->id;
                $pharmacy->save();
            }

            #INSERT TO PRESCRIPTION LOGS
            $logs = new PrescriptionLog;
            $logs->prescription_id = $request->id;
            $logs->origin = $request->origin;
            $logs->released_by = Auth::id();
            $logs->released_to = $released_to;
            $logs->remarks = "<span class='badge bg-success text-white'>Approved <i class='bi bi-hand-thumbs-up-fill'></i></span><span class='ms-2'>" . $request->remarks . "</span>";
            $logs->save();

            $prescription->forceDelete();
            return response(['code' => 200, 'message' => 'Prescription approved']);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'approvePrescription', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => "Something went wrong"]);
        }
    }

    public function rejectPrescription(Request $request)
    {
        try {
            if ($request->origin == "NURSE")
                $prescription = NurseReview::where('prescription_id', $request->id);
            else if ($request->origin == "ICC")
                $prescription = IccReview::where('prescription_id', $request->id);
            else
                $prescription = PharmacyReview::where('prescription_id', $request->id);

            if (!$prescription) {
                return response(['code' => 500, 'message' => 'Prescription dont exists']);
            }

            $returned = new ReturnedPrescription;
            $returned->prescription_id = $request->id;
            $returned->save();

            #INSERT TO PRESCRIPTION LOGS
            $logs = new PrescriptionLog;
            $logs->prescription_id = $request->id;
            $logs->origin = $request->origin;
            $logs->released_by = Auth::id();
            $logs->released_to = "DOCTOR";
            $logs->remarks = "<span class='badge bg-danger text-white'>Rejected <i class='bi bi-hand-thumbs-down-fill'></i></span><span class='ms-2'>" . $request->remarks . "</span>";
            $logs->save();

            #AFTER RETURNED DELETE RECORD
            $prescription->forceDelete();
            $doctor = Prescription::find($request->id);
            return response(['code' => 200, 'message' => 'Prescription returned', 'id' => $doctor->physician_id]);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'rejectPrescription', $request, $exception->getMessage());
            return response(['code' => 200, 'message' => "Something went wrong"]);
        }
    }

    public function delete($id)
    {
        try {
            $prescription = Prescription::find($id);
            $prescription->forceDelete();
            return response(['code' => 200, 'message' => 'Prescription deleted']);
        } catch (\Exception $exception) {
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
