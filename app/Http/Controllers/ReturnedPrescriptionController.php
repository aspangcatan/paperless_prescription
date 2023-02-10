<?php

namespace App\Http\Controllers;

use App\Models\CultureSent;
use App\Models\DysfunctionCriteria;
use App\Models\FocusInfection;
use App\Models\Immunocompromising;
use App\Models\IndicationUse;
use App\Models\IndicationUseDetails;
use App\Models\InfectiousDiagnosis;
use App\Models\MicrobiologicalResult;
use App\Models\NurseReview;
use App\Models\PatientHistory;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use App\Models\PrescriptionLog;
use App\Models\ReturnedPrescription;
use App\Models\SepsisCriteria;
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
                ->get(['p.*', 'h.hospital_id', 'h.name', 'h.birthdate', 'h.sex']);
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
            $prescription->drug_allergy = null;
            $prescription->weight = null;
            $prescription->height = null;
            $prescription->serum_creatinine = null;
            $prescription->creatinine_clearance = null;
            $prescription->sgpt = null;
            $prescription->sgot = null;
            if ($patient['type'] == "2") {
                $prescription->drug_allergy = $patient['drug_allergy'];
                $prescription->weight = $patient['weight'];
                $prescription->height = $patient['height'];
                $prescription->serum_creatinine = $patient['serum_creatinine'];
                $prescription->creatinine_clearance = $patient['creatinine_clearance'];
                $prescription->sgpt = $patient['sgpt'];
                $prescription->sgot = $patient['sgot'];
            }

            $prescription->save();

            #REINSERT AMS
            if ($patient['type'] == "2") {
                $indication_use_param = $request->indication_use;
                $indication = IndicationUse::where('prescription_id', $prescription_id)->first();
                $indication->description = $indication_use_param['description'];
                $indication->reason = $indication_use_param['reason'];
                $indication->save();

                #INSERT MICROBIOLOGICAL
                $microbiological_origin = MicrobiologicalResult::where('prescription_id', $prescription_id);
                $microbiological_origin->forceDelete();

                $microbiologicals = $request->microbiologicals;
                for ($i = 0; $i < count($microbiologicals); $i++) {
                    $microbiologicals[$i]['prescription_id'] = $prescription_id;
                }
                MicrobiologicalResult::insert($microbiologicals);

            } else {
                #DELETE AMS DATA
                $indication = IndicationUse::where('prescription_id', $prescription_id)->first();
                $indication->forceDelete();

                $focus_infection_origin = FocusInfection::where('prescription_id', $prescription_id);
                $focus_infection_origin->forceDelete();

                $culture_sent_origin = CultureSent::where('prescription_id', $prescription_id);
                $culture_sent_origin->forceDelete();

                $immunocompromising_conditions_origin = Immunocompromising::where('prescription_id', $prescription_id);
                $immunocompromising_conditions_origin->forceDelete();

                $infectious_diagnosis = InfectiousDiagnosis::where('prescription_id', $prescription_id)->first();
                $infectious_diagnosis->forceDelete();

                $sepsis_criterias_origin = SepsisCriteria::where('prescription_id', $prescription_id);
                $sepsis_criterias_origin->forceDelete();

                $dysfunction_criterias_origin = DysfunctionCriteria::where('prescription_id', $prescription_id);
                $dysfunction_criterias_origin->forceDelete();
            }

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

            #INSERT LOGS IF REGULAR PRESCRIPTION
            $logs = new PrescriptionLog;
            $logs->prescription_id = $prescription_id;
            $logs->origin = "DOCTOR";
            $logs->released_by = 1116;
            $logs->released_to = "NURSE";
            $logs->remarks = $patient['remarks'];
            $logs->save();

            #TODO: ICC REVIEW
            #FOR THE MEANTIME SET AS NURSE
            $nurse = NurseReview::where('prescription_id', $prescription_id)->first();
            if (!$nurse) {
                $nurse = new NurseReview();
                $nurse->prescription_id = $prescription_id;
                $nurse->save();
            }

            $returned_prescription = ReturnedPrescription::where('prescription_id', $prescription_id)->first();
            if ($returned_prescription) {
                $returned_prescription->forceDelete();
            }
            return response(['code' => 200, 'message' => 'Prescription updated']);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
}
