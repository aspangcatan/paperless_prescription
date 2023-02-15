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
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use App\Models\PrescriptionLog;
use App\Models\ReturnedPrescription;
use App\Models\SepsisCriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturnedPrescriptionController extends Controller
{
    public function getPrescriptions(Request $request)
    {
        try {
            $search_filters = [['p.physician_id', '=', Auth::id()]];

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
                ->get(['p.*', 'h.hospital_id', 'h.name', 'h.birthdate', 'h.sex', 'n.created_at as date_forwarded']);
            return response(['code' => 200, 'results' => $prescriptions]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function updatePrescription(Request $request)
    {
        $prescription_id = $request->id;
        try {
            #UPDATE URGENCY
            $prescription = Prescription::find($prescription_id);
            if (!$prescription) {
                return response(['code' => 500, 'message' => 'Prescription dont exists']);
            }

            $patient = $request->patient;

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

            #REINSERT AMS
            if ($patient['type'] == "2") {
                $indication_use_param = $request->indication_use;
                $indication = IndicationUse::where('prescription_id', $prescription_id)->first();
                if (!$indication) {
                    $indication = new IndicationUse;
                    $indication->prescription_id = $prescription_id;
                }
                $indication->description = $indication_use_param['description'];
                $indication->save();
            } else if ($patient['type'] == "4") {
                $intervention_param = $request->intervention;
                $intervention = InterventionDetail::where('prescription_id', $prescription_id)->first();
                if (!$intervention) {
                    $intervention = new InterventionDetail;
                    $intervention->prescription_id = $prescription_id;
                }
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

            #DONT REMOVE CONCURRENT THERAPY THAT ARE APPROVED BY THE IPCC
            #REQUEST FROM IT TO MANUALLY DELETE THE CONCURRENT THERAPY
            $concurrent_therapy = $request->concurrent_therapy;
            for ($i = 0; $i < count($concurrent_therapy); $i++) {
                $concurrent_therapy[$i]['prescription_id'] = $prescription_id;
            }
            ConcurrentTherapy::insert($concurrent_therapy);

            $patient_history = PatientHistory::where('prescription_id', $prescription_id)->first();
            $patient_history->prescription_id = $prescription_id;
            $patient_history->hospital_id = $patient['hospital_id'];
            $patient_history->name = $patient['name'];
            $patient_history->birthdate = $patient['birthdate'];
            $patient_history->sex = $patient['sex'];
            $patient_history->save();

            #DELETE ALL MEDICATIONS
            #INSERT ALL MEDICATIONS AS BULK
            $delete_medications = PrescribedMedicine::where('prescription_id', $prescription_id);
            $delete_medications->forceDelete();

            $medications = $request->medications;
            for ($i = 0; $i < count($medications); $i++) {
                $medications[$i]['prescription_id'] = $prescription_id;
            }

            PrescribedMedicine::insert($medications);
            $released_to = "ICC";
            $icc = IccReview::where('prescription_id', $prescription_id)->first();
            if (!$icc) {
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

            $returned_prescription = ReturnedPrescription::where('prescription_id', $prescription_id)->first();
            if ($returned_prescription) {
                $returned_prescription->forceDelete();
            }
            return response(['code' => 200, 'message' => 'Prescription updated']);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'updatePrescription', $request, $exception->getMessage());
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
