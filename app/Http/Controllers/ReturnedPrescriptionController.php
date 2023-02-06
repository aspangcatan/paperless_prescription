<?php

namespace App\Http\Controllers;

use App\Models\CultureSent;
use App\Models\DysfunctionCriteria;
use App\Models\FocusInfection;
use App\Models\Immunocompromising;
use App\Models\IndicationUse;
use App\Models\IndicationUseDetails;
use App\Models\InfectiousDiagnosis;
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
            $prescription->drug_allergy = $patient['drug_allergy'];
            $prescription->weight = $patient['weight'];
            $prescription->height = $patient['height'];
            $prescription->serum_creatinine = $patient['serum_creatinine'];
            $prescription->creatinine_clearance = $patient['creatinine_clearance'];
            $prescription->sgpt = $patient['sgpt'];
            $prescription->sgot = $patient['sgot'];
            $prescription->save();

            #REINSERT AMS
            if ($patient['type'] == "2") {

                $indication_use_param = $request->indication_use;
                $indication = IndicationUse::where('prescription_id', $prescription_id)->first();
                $indication->prescription_id = $prescription_id;
                $indication->description = $indication_use_param['description'];
                $indication->definitive_date = $indication_use_param['definitive_date'];
                $indication->specimen = $indication_use_param['specimen'];
                $indication->save();

                #INSERT INDICATION DETAILS
                $indication_details_origin = IndicationUseDetails::where('indication_id', $indication->id);
                $indication_details_origin->forceDelete();
                $indication_details_list = $indication_use_param['organisms'];
                for ($i = 0; $i < count($indication_details_list); $i++) {
                    $indication_details_list[$i]['indication_id'] = $indication->id;
                }
                IndicationUseDetails::insert($indication_details_list);

                #INSERT FOCUS INFECTIONS
                $focus_infection_origin = FocusInfection::where('prescription_id', $prescription_id);
                $focus_infection_origin->forceDelete();
                $focus_infection_params = $request->focus_infections;
                for ($i = 0; $i < count($focus_infection_params); $i++) {
                    $focus_infection_params[$i]['prescription_id'] = $prescription_id;
                }
                FocusInfection::insert($focus_infection_params);

                #INSERT CULTURE SENT
                $culture_sent_origin = CultureSent::where('prescription_id', $prescription_id);
                $culture_sent_origin->forceDelete();
                $culture_sent_params = $request->culture_sents;
                for ($i = 0; $i < count($culture_sent_params); $i++) {
                    $culture_sent_params[$i]['prescription_id'] = $prescription_id;
                }
                CultureSent::insert($culture_sent_params);

                #INSERT IMMUNOCOMPROMISING CONDITION
                $immunocompromising_conditions_origin = Immunocompromising::where('prescription_id', $prescription_id);
                $immunocompromising_conditions_origin->forceDelete();
                $immunocompromising_conditions_params = $request->immunocompromising_conditions;
                for ($i = 0; $i < count($immunocompromising_conditions_params); $i++) {
                    $immunocompromising_conditions_params[$i]['prescription_id'] = $prescription_id;
                }
                Immunocompromising::insert($immunocompromising_conditions_params);

                #INSERT INFECTIOUS DIAGNOSIS
                $infectious_diagnosis_params = $request->infectious_diagnosis;
                $infectious_diagnosis = InfectiousDiagnosis::where('prescription_id', $prescription_id)->first();
                $infectious_diagnosis->description = $infectious_diagnosis_params['description'];
                $infectious_diagnosis->details = $infectious_diagnosis_params['details'];
                $infectious_diagnosis->save();

                #INSERT SEPSIS CRITERIA
                $sepsis_criterias_origin = SepsisCriteria::where('prescription_id', $prescription_id);
                $sepsis_criterias_origin->forceDelete();
                $sepsis_criterias_params = $request->sepsis_criterias;
                for ($i = 0; $i < count($sepsis_criterias_params); $i++) {
                    $sepsis_criterias_params[$i]['prescription_id'] = $prescription_id;
                }
                SepsisCriteria::insert($sepsis_criterias_params);

                #INSERT SEPSIS CRITERIA
                $dysfunction_criterias_origin = DysfunctionCriteria::where('prescription_id', $prescription_id);
                $dysfunction_criterias_origin->forceDelete();
                $dysfunction_criterias_params = $request->dysfunction_criterias;
                for ($i = 0; $i < count($dysfunction_criterias_params); $i++) {
                    $dysfunction_criterias_params[$i]['prescription_id'] = $prescription_id;
                }
                DysfunctionCriteria::insert($dysfunction_criterias_params);
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
