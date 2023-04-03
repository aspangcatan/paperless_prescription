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
use App\Models\ReturnedMeds;
use App\Models\ReturnedPrescription;
use App\Models\SepsisCriteria;
use Carbon\Carbon;
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

    public function getLatestPrescription(Request $request)
    {
        try {
            $results = Prescription::where('hospital_id', $request->hospital_id)->orderBy('id', 'DESC')->first();
            if ($results) {
                return response(['code' => 200, 'result' => $results]);
            } else {
                return response(['code' => 500, 'message' => 'No previous information']);
            }
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function getPrescription(Request $request)
    {
        try {
            $search_filters = [['p.status', '=', 1]];
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
                ->skip($request->page * 10)
                ->take(11)
                ->get(['p.*', 'h.hospital_id', 'h.name', 'h.birthdate', 'h.sex']);
            return response(['code' => 200, 'results' => $prescriptions]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function trackPrescription(Request $request)
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

            $prescriptions = DB::table('paperless_prescription.prescriptions as p')
                ->join('paperless_prescription.patient_history as h', 'p.id', '=', 'h.prescription_id')
                ->where($search_filters)
                ->skip($request->page * 10)
                ->take(11)
                ->orderBy('p.id', 'DESC')
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
            $prescription->attending_physician = $patient['attending_physician'];
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
            $prescription->additional_labs = $patient['additional_labs'];

            $prescription->save();
            $prescription_id = $prescription->id;

            #INSERT PATIENT HISTORY
            $patient_history = new PatientHistory;
            $patient_history->prescription_id = $prescription_id;
            $patient_history->hospital_id = $patient['hospital_id'];
            $patient_history->name = $patient['name'];
            $patient_history->birthdate = $patient['birthdate'];
            $patient_history->sex = $patient['sex'];
            $patient_history->save();

            if ($patient['type'] == "6") {
                $nurse = new NurseReview();
                $nurse->prescription_id = $prescription_id;
                $nurse->save();
                $nurse->save();

                $icc = new IccReview();
                $icc->prescription_id = $prescription_id;
                $icc->save();

                $pharmacy = new PharmacyReview();
                $pharmacy->prescription_id = $prescription_id;
                $pharmacy->save();

                #INSERT LOGS IF REGULAR PRESCRIPTION
                $logs = new PrescriptionLog;
                $logs->prescription_id = $prescription_id;
                $logs->origin = "DOCTOR";
                $logs->released_by = Auth::id();
                $logs->released_to = "IPCC,NURSE,PHARMACY";
                $logs->remarks = $patient['remarks'];
                $logs->save();

                return response(['code' => 200, 'message' => 'Prescribe successfully']);
            }

            if ($patient['type'] != "1") {
                if ($patient['type'] == "2") {
                    #INSERT INDICATION USE
                    $indication_use_param = $request->indication_use;
                    $indication = new IndicationUse;
                    $indication->prescription_id = $prescription_id;
                    $indication->description = $indication_use_param['description'];
                    $indication->save();
                } else if ($patient['type'] == "4" || $patient['type'] == "7") {
                    #UPDATE FOR SWITCH ANG PREVIOUS CONCURRENT
                    $intervention_param = $request->intervention;
                    if ($intervention_param['concurrent_id'] != "0") {
                        $selected_concurrent_id = ConcurrentTherapy::find($intervention_param['concurrent_id']);
                        if ($selected_concurrent_id) {
                            $selected_concurrent_id->for_switch = $prescription_id;
                            $selected_concurrent_id->save();
                        }
                    }
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
                    if ($concurrent_therapy[$i]['for_switch'] == "1") {
                        $concurrent_therapy[$i]['for_switch'] = $prescription_id;
                    }
                }
                ConcurrentTherapy::insert($concurrent_therapy);
            }

            #INSERT ALL MEDICATIONS AS BULK
            $medications = $request->medications;
            for ($i = 0; $i < count($medications); $i++) {
                $medications[$i]['prescription_id'] = $prescription_id;
            }
            PrescribedMedicine::insert($medications);

            #IF MONITORED PRESCRIPTION, INSERT DATA IN CONCURRENT SET AS 1 DAY APPROVED
            if ($patient['type'] == '5') {
                for ($i = 0; $i < count($medications); $i++) {
                    $concurrent = new ConcurrentTherapy;
                    $concurrent->prescription_id = $prescription_id;
                    $concurrent->hospital_id = $patient['hospital_id'];
                    $concurrent->concurrent_date = Carbon::now();
                    $concurrent->antimicrobial = $medications[$i]['description'];
                    $concurrent->dosing_regimen = $medications[$i]['dosage_strength'] . '' . $medications[$i]['dosage_unit'] . ' ' . $medications[$i]['frequency'];
                    $concurrent->start_date = Carbon::now();
                    $concurrent->stop_date = Carbon::now();
                    $concurrent->days_approved = 1;
                    $concurrent->date_approved = Carbon::now();
                    $concurrent->save();
                }
            }

            $released_to = "NURSE";
            if ($patient['type'] == '1' || $patient['type'] == '5') {
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
                $prescription = NurseReview::where('prescription_id', $request->id)->first();
            else {
                $prescription = IccReview::where('prescription_id', $request->id)->first();
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
                    ->get();

                #CHECK IF TYPE IS INTERVENTION FORM
                #UPDATE SELECTED AMS STOP DATE INTO TODAYS DATE
                if ($request->type == "4" || $request->type == "7") {
                    $get_selected_concurrent = ConcurrentTherapy::where("for_switch", $request->id)->first();
                    if (!$get_selected_concurrent) {
                        return response(['code' => 500, 'message' => 'You did not specify the antibiotic you wish to be switched']);
                    }
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

//                #SET QUANTITY ORDERED
//                $prescribed_meds = PrescribedMedicine::where('prescription_id', $request->id)->first();
//                $prescribed_meds->quantity_ordered = $request->updated_quantity;
//                $prescribed_meds->save();

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
                $prescription = NurseReview::where('prescription_id', $request->id)->first();
            } else if ($request->origin == "ICC")
                $prescription = IccReview::where('prescription_id', $request->id)->first();
            else
                $prescription = PharmacyReview::where('prescription_id', $request->id)->first();

            if (!$prescription) {
                return response(['code' => 500, 'message' => 'Prescription dont exists']);
            }

            if ($request->type == "6") {
                #GET ALL NURSE PENDING
                #GET PRESCRIPTION CREATED AT BY THIS PHYSICIAN
                $current_prescrption = Prescription::find($request->id);
                if ($request->origin == "NURSE") {
                    $pending_prescriptions = DB::table('paperless_prescription.nurse_reviews as n')
                        ->join('paperless_prescription.prescriptions as p', 'n.prescription_id', '=', 'p.id')
                        ->where('p.created_at', '<=', $current_prescrption->created_at)
                        ->where('p.physician_id', '=', $current_prescrption->physician_id)
                        ->where('p.hospital_id', '=', $current_prescrption->hospital_id)
                        ->get(['n.id', 'n.prescription_id']);
                } else if ($request->origin == "ICC") {
                    $pending_prescriptions = DB::table('paperless_prescription.icc_reviews as n')
                        ->join('paperless_prescription.prescriptions as p', 'n.prescription_id', '=', 'p.id')
                        ->where('p.created_at', '<=', $current_prescrption->created_at)
                        ->where('p.physician_id', '=', $current_prescrption->physician_id)
                        ->where('p.hospital_id', '=', $current_prescrption->hospital_id)
                        ->get(['n.id', 'n.prescription_id']);
                } else {
                    $pending_prescriptions = DB::table('paperless_prescription.pharmacy_reviews as n')
                        ->join('paperless_prescription.prescriptions as p', 'n.prescription_id', '=', 'p.id')
                        ->where('p.created_at', '<=', $current_prescrption->created_at)
                        ->where('p.physician_id', '=', $current_prescrption->physician_id)
                        ->where('p.hospital_id', '=', $current_prescrption->hospital_id)
                        ->get(['n.id', 'n.prescription_id']);
                }


                #GET ALL ANTIBIOTIC AND UPDATE STOP DATE INTO DATE PRESCRIBED
                $stop_date = date('Y-m-d', strtotime($current_prescrption->created_at));
                $concurrent_therapies = DB::table('paperless_prescription.concurrent_therapy as c')
                    ->join('paperless_prescription.prescriptions as p', 'c.prescription_id', '=', 'p.id')
                    ->where('p.hospital_id', '=', $current_prescrption->hospital_id)
                    ->where('p.physician_id', '=', $current_prescrption->physician_id)
                    ->where('c.stop_date', '>', $stop_date)
                    ->get(['c.id']);

                for ($i = 0; $i < count($concurrent_therapies); $i++) {
                    $current_therapy = ConcurrentTherapy::find($concurrent_therapies[$i]->id);
                    $current_therapy->stop_date = $stop_date;
                    $current_therapy->save();
                }

                for ($i = 0; $i < count($pending_prescriptions); $i++) {
                    $returned = ReturnedPrescription::where('prescription_id', $pending_prescriptions[$i]->prescription_id)->first();
                    if (!$returned) {
                        $returned = new ReturnedPrescription;
                        $returned->prescription_id = $pending_prescriptions[$i]->prescription_id;
                        $returned->save();
                    }

                    #INSERT TO PRESCRIPTION LOGS
                    $logs = new PrescriptionLog;
                    $logs->prescription_id = $pending_prescriptions[$i]->prescription_id;
                    $logs->origin = $request->origin;
                    $logs->released_by = Auth::id();
                    $logs->released_to = "DOCTOR";
                    $logs->remarks = "<span class='badge bg-danger text-white'>Rejected <i class='bi bi-hand-thumbs-down-fill'></i></span><span class='ms-2'>" . $request->remarks . "</span>";
                    $logs->save();

                    if ($request->origin == "NURSE") {
                        $prescription_index = NurseReview::find($pending_prescriptions[$i]->id);
                    } else if ($request->origin == "ICC") {
                        $prescription_index = IccReview::find($pending_prescriptions[$i]->id);
                    } else {
                        $prescription_index = PharmacyReview::find($pending_prescriptions[$i]->id);
                    }

                    $prescription_index->forceDelete();
                }


                return response(['code' => 200, 'message' => 'All Prescription returned', 'id' => $current_prescrption->physician_id]);
            }

            #CHECK IF INTERVENTION FORM
            if ($request->origin != "ICC") {
                $current_concurrent = ConcurrentTherapy::where('prescription_id', $request->id)->whereNotNull(['date_approved']);
                $current_concurrent->forceDelete();

                if ($request->type == "4" || $request->type == "7") {
                    //COMPUTE STOP DATE VIA START DATE AND DAYS APPROVED
                    $switched_concurrent = ConcurrentTherapy::where('for_switch', $request->id)->first();
                    $date = Carbon::parse($switched_concurrent->start_date);
                    $date = $date->addDays($switched_concurrent->days_approved - 1);
                    $stop_date = date('Y-m-d', strtotime($date));
                    $switched_concurrent->stop_date = $stop_date;
                    $switched_concurrent->save();
                }
            }

            $returned = ReturnedPrescription::where('prescription_id', $request->id)->first();
            if (!$returned) {
                $returned = new ReturnedPrescription;
                $returned->prescription_id = $request->id;
                $returned->save();
            }

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

    public function deletePrescription(Request $request)
    {
        try {
            $returned_prescription = ReturnedPrescription::where('prescription_id', $request->id)->first();
            if (!$returned_prescription) {
                return response(['code'=>500,'message'=>'Prescription ID dont exists']);
            }

            $prescription = Prescription::find($request->id);
            $prescription->status = 0;
            $prescription->save();

            #INSERT TO PRESCRIPTION LOGS
            $logs = new PrescriptionLog;
            $logs->prescription_id = $request->id;
            $logs->origin = "Doctor";
            $logs->released_by = Auth::id();
            $logs->released_to = "Cycle End";
            $logs->remarks = "<span class='badge bg-success text-white'>Cycle End <i class='bi bi-hand-thumbs-up-fill'></i></span><span class='ms-2'>" . $request->remarks . "</span>";
            $logs->save();

            $returned_prescription->forceDelete();
            return response(['code' => 200, 'message' => 'Prescription deleted']);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'deletePrescription', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => $exception->getMessage()]);
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
            $type = $request->filter_type;

            $ams_summary = DB::table('paperless_prescription.concurrent_therapy as c')
                ->select(DB::raw('ph.name,TIMESTAMPDIFF(YEAR, ph.birthdate, CURDATE()) as age,ph.birthdate,ph.sex,p.station,p.created_at,CONCAT(u.fname," ",u.mname," ",u.lname) as physician,
                c.antimicrobial,CONCAT(a.fname," ",a.mname," ",a.lname) as approved_by,c.days_approved,c.start_date,c.stop_date,c.date_approved,p.attending_physician'))
                ->join('paperless_prescription.patient_history as ph', 'c.prescription_id', '=', 'ph.prescription_id')
                ->join('paperless_prescription.prescriptions as p', 'c.prescription_id', '=', 'p.id')
                ->join('tdh_user.users as u', 'p.physician_id', '=', 'u.id')
                ->leftJoin('tdh_user.users as a', 'c.approved_by', '=', 'a.id')
                ->where(function ($query) use ($patient, $description, $from_date, $to_date, $type) {
                    #COMMENT THIS IF YOU WANT TO INCLUDED MANUALLY ADDED CONCURRENT ANTIBIOTIC THERAPY
                    $query->whereNotNull('c.date_approved');
                    if ($type == "1")
                        $query->where('p.type', '=', '5');
                    else if ($type == "0")
                        $query->where('p.type', '<>', '5');

                    if ($patient != "") {
                        $query->where('ph.name', 'LIKE', '%' . $patient . '%');
                    }

                    if ($description != "") {
                        $query->where('antimicrobial', 'LIKE', '%' . $description . '%');
                    }

                    if ($from_date != "") {
                        $query->whereRaw("STR_TO_DATE(c.start_date,'%Y-%m-%d') >= STR_TO_DATE('" . $from_date . "','%Y-%m-%d') AND STR_TO_DATE(c.stop_date,'%Y-%m-%d') <= STR_TO_DATE('" . $to_date . "','%Y-%m-%d')");
                    }
                })
                ->get();

            self::insertErrorLogs(Auth::id(), 'getAmsSummary', $request, "N/A");
            return response(['code' => 200, 'results' => $ams_summary]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function getMonitoredSummary(Request $request)
    {
        try {
            $patient = $request->filter_patient;
            $description = $request->filter_description;
            $from_date = $request->from_date;
            $to_date = $request->to_date;

            $monitored_summary = DB::table('paperless_prescription.prescriptions as p')
                ->select(DB::raw('ph.name,TIMESTAMPDIFF(YEAR, ph.birthdate, CURDATE()) as age,ph.birthdate,ph.sex,p.station,p.created_at,
                CONCAT(u.fname," ",u.mname," ",u.lname) as physician,c.description,
                COALESCE(c.quantity_rendered,"") as quantity_rendered,COALESCE(c.quantity_returned,"") as quantity_returned'))
                ->join('paperless_prescription.patient_history as ph', 'p.id', '=', 'ph.prescription_id')
                ->join('paperless_prescription.prescribed_medicines as c', 'c.prescription_id', '=', 'p.id')
                ->join('tdh_user.users as u', 'p.physician_id', '=', 'u.id')
                ->where(function ($query) use ($patient, $description, $from_date, $to_date) {
                    #COMMENT THIS IF YOU WANT TO INCLUDED MANUALLY ADDED CONCURRENT ANTIBIOTIC THERAPY
                    $query->whereNotNull('c.date_approved');
                    #UNCOMMENT THIS IF YOU DONT WANT TO INCLUDE MONITORED AMS

                    $query->where('p.type', '<>', '5');
                    if ($patient) {
                        $query->where('ph.name', 'LIKE', '%' . $patient . '%');
                    }
                    if ($description) {
                        $query->where('c.description', 'LIKE', '%' . $description . '%');
                    }
                    if ($from_date) {
                        $query->whereRaw("STR_TO_DATE(p.created_at,'%Y-%m-%d') BETWEEN STR_TO_DATE('" . $from_date . "','%Y-%m-%d') AND STR_TO_DATE('" . $to_date . "','%Y-%m-%d')");
                    }
                })
                ->get();

            return response(['code' => 200, 'results' => $monitored_summary]);
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
