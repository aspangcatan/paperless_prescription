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
use App\Models\InterventionDetail;
use App\Models\MicrobiologicalResult;
use App\Models\NurseReview;
use App\Models\PatientHistory;
use App\Models\PharmacyReview;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use App\Models\PrescriptionLog;
use App\Models\ReturnedMeds;
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
                ->skip($request->page * 3)
                ->take(11)
                ->get(['p.*', 'h.hospital_id', 'h.name', 'h.birthdate', 'h.sex']);
            return response(['code' => 200, 'results' => $prescriptions]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function getPrescriptionDetails($id)
    {
        $ams = [];
        try {
            $prescription = Prescription::find($id);
            if ($prescription->type == '2') {
                $indication = IndicationUse::where('prescription_id', $id)->first();
                $ams['indication_use'] = $indication;
            }

            if ($prescription->type != '4') {
                $microbiologicals = MicrobiologicalResult::where('prescription_id', $id)->get();
                $ams['microbiologicals'] = $microbiologicals;
            } else {
                $intervention = InterventionDetail::where('prescription_id', $id)->first();
                $ams['intervention'] = $intervention;
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
            $concurrent = ConcurrentTherapy::where('hospital_id', $request->hospital_id)
                ->orderBy('start_date', 'ASC')
                ->get();
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
            $prescription->drug_allergy = $patient['drug_allergy'];
            $prescription->weight = $patient['weight'];
            $prescription->height = $patient['height'];
            $prescription->serum_creatinine = $patient['serum_creatinine'];
            $prescription->creatinine_clearance = $patient['creatinine_clearance'];
            $prescription->transaminases = $patient['transaminases'];
            $prescription->sgpt = $patient['sgpt'];
            $prescription->sgot = $patient['sgot'];
            $prescription->days_requested = $patient['days_requested'];

            $prescription->save();
            $prescription_id = $prescription->id;

            if ($patient['type'] != "1") {

                $intervention_param = $request->intervention;
                if ($patient['type'] == "2") {
                    #INSERT INDICATION USE
                    $indication_use_param = $request->indication_use;
                    $indication = new IndicationUse;
                    $indication->prescription_id = $prescription_id;
                    $indication->description = $indication_use_param['description'];
                    $indication->save();
                } else if ($patient['type'] == "4") {
                    $intervention = new InterventionDetail;
                    $intervention->prescription_id = $prescription_id;
                    $intervention->clinical_stability = $intervention_param['clinical_stability'];
                    $intervention->oral_intake = $intervention_param['oral_intake'];
                    $intervention->save();
                }

                #INSERT MICROBIOLOGICAL
                if ($patient['type'] != '4') {
                    $microbiologicals = $request->microbiologicals;
                    for ($i = 0; $i < count($microbiologicals); $i++) {
                        $microbiologicals[$i]['prescription_id'] = $prescription_id;
                    }
                    MicrobiologicalResult::insert($microbiologicals);
                }

                $concurrent_therapy = $request->concurrent_therapy;
                for ($i = 0; $i < count($concurrent_therapy); $i++) {
                    $concurrent_therapy[$i]['prescription_id'] = $prescription_id;
                    if ($concurrent_therapy[$i]['for_switch'] != "0") {
                        $concurrent_therapy[$i]['for_switch'] = $prescription_id;
                    }
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
            if ($patient['type'] == '1') {
                $nurse = new NurseReview();
                $nurse->prescription_id = $prescription_id;
                $nurse->save();
            } else {
                $released_to = "IPCC";
                $icc = new IccReview();
                $icc->prescription_id = $prescription_id;
                $icc->save();
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
            else {
                $prescription = IccReview::where('prescription_id', $request->id);
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

                #CHECK IF TYPE IS INTERVENTION FORM
                #UPDATE SELECTED AMS STOP DATE INTO TODAYS DATE
                if ($request->type == "4") {
                    $get_selected_concurrent = ConcurrentTherapy::where("for_switch", $request->id)->first();
                    $get_selected_concurrent->stop_date = $date_approved;
                    $get_selected_concurrent->save();
                }

                for ($i = 0; $i < count($medications); $i++) {
                    $concurrent = new ConcurrentTherapy;
                    $concurrent->prescription_id = $request->id;
                    $concurrent->hospital_id = $request->hospital_id;
                    $concurrent->concurrent_date = $date_approved;
                    $concurrent->antimicrobial = $medications[$i]->description;
                    $concurrent->dosing_regimen = $medications[$i]->dosage_strength . '' . $medications[$i]->dosage_unit . ' ' . $medications[$i]->frequency;
                    $concurrent->start_date = $date_approved;
                    $concurrent->stop_date = $stop_date;
                    $concurrent->days_approved = $request->days_approved;
                    $concurrent->approved_by = Auth::id();
                    $concurrent->date_approved = $date_approved;
                    $concurrent->save();
                }

                #SET QUANTITY ORDERED
                $prescribed_meds = PrescribedMedicine::where('prescription_id', $request->id)->first();
                $prescribed_meds->quantity_ordered = $request->updated_quantity;
                $prescribed_meds->save();

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


            if ($request->origin == "NURSE") {
                $prescription = NurseReview::where('prescription_id', $request->id);
            } else if ($request->origin == "ICC")
                $prescription = IccReview::where('prescription_id', $request->id);
            else
                $prescription = PharmacyReview::where('prescription_id', $request->id);

            if (!$prescription) {
                return response(['code' => 500, 'message' => 'Prescription dont exists']);
            }

            #CHECK IF INTERVENTION FORM
            if ($request->origin != "ICC") {

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
            $returned = ReturnedPrescription::where('prescription_id', $request->id);
            $returned->forceDelete();
            self::insertErrorLogs(Auth::id(), 'rejectPrescription', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => "Something went wrong"]);
        }
    }

    public function returnMeds(Request $request)
    {
        try {
            #CHECK IF CURRENTLY ON HAND IS GREATER THAN 0;
            $prescribed_meds = PrescribedMedicine::find($request->id);
            if (!$prescribed_meds) {
                return response(['code' => 500, 'message' => 'Prescribed medicine not found']);
            }

            if ($prescribed_meds->quantity_rendered < ($prescribed_meds->quantity_returned + $request->amount)) {
                return response(['code' => 500, 'message' => 'Prescribed medicine on hand is less than 1']);
            }

            #UPDATE ON HAND
            $prescribed_meds->quantity_returned = ($prescribed_meds->quantity_returned + $request->amount);
            $prescribed_meds->save();

            #INSERT RECORD TO RETURNED MEDS
            $returned_meds = new ReturnedMeds;
            $returned_meds->prescribed_meds_id = $request->id;
            $returned_meds->returned_by = Auth::id();
            $returned_meds->returned_amount = $request->amount;
            $returned_meds->remarks = $request->remarks;
            $returned_meds->save();
            return response(['code' => 200, 'message' => 'Successfully returned']);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'returnMeds', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => "Something went wrong"]);
        }
    }

    public function getAmsSummary(Request $request)
    {
        try {
            $patient = $request->filter_patient;
            $description = $request->filter_description;
            $from_date = $request->from_date;
            $to_date = $request->to_date;

            $ams_summary = DB::table('paperless_prescription.concurrent_therapy as c')
                ->select(DB::raw('ph.name,TIMESTAMPDIFF(YEAR, ph.birthdate, CURDATE()) as age,ph.birthdate,ph.sex,p.station,p.created_at,CONCAT(u.fname," ",u.mname," ",u.lname) as physician,
                c.antimicrobial,CONCAT(a.fname," ",a.mname," ",a.lname) as approved_by,c.days_approved,c.start_date,c.stop_date,c.date_approved'))
                ->join('paperless_prescription.patient_history as ph', 'c.prescription_id', '=', 'ph.prescription_id')
                ->join('paperless_prescription.prescriptions as p', 'c.prescription_id', '=', 'p.id')
                ->join('tdh_user.users as u', 'p.physician_id', '=', 'u.id')
                ->leftJoin('tdh_user.users as a', 'c.approved_by', '=', 'a.id')
                ->where(function ($query) use ($patient, $description, $from_date, $to_date) {
                    if ($patient) {
                        $query->where('ph.name', 'LIKE', '%' . $patient . '%');
                    }
                    if ($description) {
                        $query->where('antimicrobial', 'LIKE', '%' . $description . '%');
                    }
                    if ($from_date) {
                        $query->whereRaw("STR_TO_DATE(date_approved,'%Y-%m-%d') BETWEEN STR_TO_DATE('" . $from_date . "','%Y-%m-%d') AND STR_TO_DATE('" . $to_date . "','%Y-%m-%d')");
                    }
                })
                ->get();

            return response(['code' => 200, 'results' => $ams_summary]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
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
