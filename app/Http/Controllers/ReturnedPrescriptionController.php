<?php

namespace App\Http\Controllers;

use App\Models\ConcurrentTherapy;
use App\Models\ErrorLog;
use App\Models\IccReview;
use App\Models\IndicationUse;
use App\Models\MicrobiologicalResult;
use App\Models\PatientHistory;
use App\Models\PharmacyReview;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use App\Models\ReturnedPrescription;
use App\Services\PrescriptionWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReturnedPrescriptionController extends Controller
{
    public function getTotalReturned(Request $request)
    {
        try {
            $search_filters = [['p.physician_id', '=', Auth::id()]];
            $prescriptions = DB::table('paperless_prescription.returned_prescriptions as n')
                ->join('paperless_prescription.prescriptions as p', 'n.prescription_id', '=', 'p.id')
                ->where($search_filters)
                ->count();
            return response(['code' => 200, 'results' => $prescriptions]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

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
                ->orderBy('n.created_at', 'DESC')
                ->get(['p.*', 'h.hospital_id', 'h.name', 'h.birthdate', 'h.sex', 'n.created_at as date_forwarded']);
            return response(['code' => 200, 'results' => $prescriptions]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    /**
     * Doctor resubmits a returned Rx. Approved medicines are never touched. The submitted list holds
     * the rejected medicines being corrected (with their id) plus any new ones (no id); a rejected
     * medicine left out of the list is withdrawn. Everything submitted goes back to pending and is
     * routed again: IPCC for Restricted / for-extension, auto-approved otherwise.
     */
    public function updatePrescription(Request $request)
    {
        try {
            $patient = $request->patient;
            Validator::make(['patient' => $patient, 'id' => $request->id], [
                'id' => 'required|integer',
                'patient' => 'required|array',
                'patient.hospital_id' => 'required|string|max:255',
                'patient.name' => 'required|string|max:255',
                'patient.type' => 'required',
                'patient.station' => 'nullable|string|max:255',
                'patient.urgency' => 'nullable|string|max:20',
                'patient.remarks' => 'nullable|string',
            ])->validate();

            $indication_use_param = $request->indication_use;
            $microbiologicals = PrescriptionWorkflow::microbiologicalRows($request->microbiologicals);

            if ($patient['type'] == "7") {
                return response(['code' => 500, 'message' => 'Prescription type Switch Medication is currently disabled']);
            }

            if ($patient['type'] != "2" && $patient['type'] != "5" && $patient['type'] != "8") {
                return response(['code' => 500, 'message' => 'Prescription type are now updated to Restricted,Monitored and Non-Monitored']);
            }

            if ($patient['type'] == "2") {
                //CHECK IF INDICATION USE IS NOT EMPIRIC
                if (($indication_use_param['description'] ?? '') == "Definitive" && count($microbiologicals) < 1) {
                    return response(['code' => 500, 'message' => 'Microbiological Results is mandatory when Indication for use is Definitive']);
                }
            }

            $medications = PrescriptionWorkflow::medicineRows($request->medications);

            $result = DB::transaction(function () use ($request, $patient, $indication_use_param, $microbiologicals, $medications) {
                $returned_prescription = ReturnedPrescription::where('prescription_id', $request->id)->lockForUpdate()->first();
                if (!$returned_prescription) {
                    return ['code' => 500, 'message' => 'Prescription is no longer returned, kindly refresh'];
                }

                $prescription = Prescription::lockForUpdate()->find($request->id);
                if ($prescription->physician_id != Auth::id()) {
                    return ['code' => 500, 'message' => 'Permission denied'];
                }

                $current = PrescribedMedicine::where('prescription_id', $prescription->id)->lockForUpdate()->get()->keyBy('id');
                $has_approved = $current->contains('status', PrescribedMedicine::STATUS_APPROVED);
                if ($has_approved && (string)$patient['type'] !== (string)$prescription->type) {
                    return ['code' => 500, 'message' => 'The type cannot change while some medicines on this prescription are approved'];
                }

                foreach ($medications as $row) {
                    if ($row['id'] === null) {
                        continue;
                    }
                    $medicine = $current->get((int)$row['id']);
                    if (!$medicine || $medicine->status != PrescribedMedicine::STATUS_REJECTED) {
                        return ['code' => 500, 'message' => 'Only rejected medicines can be changed. Please refresh and try again.'];
                    }
                }

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
                $prescription->diagnosis = $patient['diagnosis'];
                $prescription->for_extension = $patient['for_extension'];
                $prescription->save();
                $prescription_id = $prescription->id;

                #FOR SWTICH BACK TO ZERO
                $previous_concurrent = ConcurrentTherapy::where('for_switch', $prescription_id)->first();
                if ($previous_concurrent) {
                    $previous_concurrent->for_switch = null;
                    $previous_concurrent->save();
                }

                if ($patient['type'] == "2") {
                    $indication = IndicationUse::firstOrNew(['prescription_id' => $prescription_id]);
                    $indication->prescription_id = $prescription_id;
                    $indication->description = $indication_use_param['description'];
                    $indication->save();
                }

                #INSERT MICROBIOLOGICAL
                MicrobiologicalResult::where('prescription_id', $prescription_id)->delete();
                foreach ($microbiologicals as $i => $row) {
                    $microbiologicals[$i]['prescription_id'] = $prescription_id;
                }
                MicrobiologicalResult::insert($microbiologicals);

                ConcurrentTherapy::insert(PrescriptionWorkflow::manualConcurrentRows($request->concurrent_therapy, $patient['hospital_id'], $prescription_id));

                $patient_history = PatientHistory::where('prescription_id', $prescription_id)->first();
                $patient_history->hospital_id = $patient['hospital_id'];
                $patient_history->name = $patient['name'];
                $patient_history->birthdate = $patient['birthdate'];
                $patient_history->sex = $patient['sex'];
                $patient_history->save();

                #CORRECT REJECTED MEDICINES, ADD NEW ONES, WITHDRAW REJECTED ONES THAT WERE REMOVED
                $kept_ids = [];
                $resubmitted = [];
                foreach ($medications as $row) {
                    if ($row['id'] === null) {
                        $resubmitted[] = PrescriptionWorkflow::createMedicine($prescription, $row);
                        continue;
                    }
                    $medicine = $current->get((int)$row['id']);
                    $medicine->description = $row['description'];
                    $medicine->unit = $row['unit'];
                    $medicine->dosage_strength = $row['dosage_strength'];
                    $medicine->dosage_unit = $row['dosage_unit'];
                    $medicine->frequency = $row['frequency'];
                    $medicine->days_requested = $row['days_requested'] ?? $medicine->days_requested;
                    $medicine->status = PrescribedMedicine::STATUS_PENDING;
                    $medicine->days_approved = null;
                    $medicine->decision_remarks = null;
                    $medicine->decided_by = null;
                    $medicine->decided_at = null;
                    $medicine->save();
                    $kept_ids[] = $medicine->id;
                    $resubmitted[] = $medicine;
                }

                PrescribedMedicine::where('prescription_id', $prescription_id)
                    ->where('status', PrescribedMedicine::STATUS_REJECTED)
                    ->whereNotIn('id', $kept_ids)
                    ->update(['status' => PrescribedMedicine::STATUS_WITHDRAWN]);

                $released_to = "ICC";
                if (PrescriptionWorkflow::skipsIcc($patient['type'], $patient['for_extension'])) {
                    foreach ($resubmitted as $medicine) {
                        PrescriptionWorkflow::approveMedicine($prescription, $medicine, PrescriptionWorkflow::AUTO_APPROVED_DAYS, null);
                    }
                    $released_to = "PHARMACY";
                    PharmacyReview::firstOrCreate(['prescription_id' => $prescription_id]);
                } else {
                    IccReview::firstOrCreate(['prescription_id' => $prescription_id]);
                }

                PrescriptionWorkflow::log($prescription_id, "DOCTOR", $released_to, $patient['remarks']);
                $returned_prescription->forceDelete();
                PrescriptionWorkflow::refreshReviewStatus($prescription_id);
                return ['code' => 200, 'message' => 'Prescription updated'];
            });

            return response($result);
        } catch (ValidationException $exception) {
            return response(['code' => 500, 'message' => collect($exception->errors())->flatten()->first()]);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'updatePrescription', $request, $exception->getMessage());
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
