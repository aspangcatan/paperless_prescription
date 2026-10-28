<?php

namespace App\Http\Controllers;

use App\Models\ConcurrentTherapy;
use App\Models\ErrorLog;
use App\Models\IccReview;
use App\Models\IndicationUse;
use App\Models\MicrobiologicalResult;
use App\Models\NurseReview;
use App\Models\PatientHistory;
use App\Models\PharmacyReview;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use App\Models\RenderedPrescription;
use App\Models\ReturnedMeds;
use App\Models\ReturnedPrescription;
use App\Services\PrescriptionWorkflow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
                ->orderBy('p.created_at', 'DESC')
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
            if (!$prescription) {
                return response(['code' => 500, 'message' => 'Prescription dont exists']);
            }

            if ($prescription->type == '2') {
                $indication = IndicationUse::where('prescription_id', $id)->first();
                $ams['indication_use'] = $indication;
            }

            if ($prescription->type != '4') {
                $microbiologicals = MicrobiologicalResult::where('prescription_id', $id)->get();
                $ams['microbiologicals'] = $microbiologicals;
            }

            #GET MEDICATIONS WITH EACH ONE'S OWN DECISION
            $medications = DB::table('paperless_prescription.prescribed_medicines as m')
                ->leftJoin('tdh_user.users as u', 'm.decided_by', '=', 'u.id')
                ->where('m.prescription_id', '=', $id)
                ->orderBy('m.id')
                ->get(['m.*', DB::raw("TRIM(CONCAT(COALESCE(u.fname, ''), ' ', COALESCE(u.lname, ''))) as decided_by_name")]);
            return response(['code' => 200, 'results' => $medications, 'ams' => $ams, 'review_status' => $prescription->review_status]);
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
            $logs = DB::table('paperless_prescription.prescription_logs as l')
                ->join('tdh_user.users as u', 'l.released_by', '=', 'u.id')
                ->leftJoin('paperless_prescription.prescribed_medicines as m', 'l.prescribed_medicine_id', '=', 'm.id')
                ->where('l.prescription_id', '=', $id)
                ->orderBy('l.id')
                ->get(['l.origin', 'u.fname', 'u.mname', 'u.lname', 'l.released_to', 'l.remarks', 'l.date_forwarded', 'm.description as medicine']);

            #STORED REMARKS MAY HOLD UNESCAPED HTML FROM BEFORE 2026-10-27: SEND PLAIN TEXT ONLY
            foreach ($logs as $log) {
                $readable = PrescriptionWorkflow::readableRemarks($log->remarks);
                $log->decision = $readable['decision'];
                $log->remarks = $readable['text'];
            }
            return response(['code' => 200, 'results' => $logs]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    public function insertPrescription(Request $request)
    {
        try {
            #PATIENT REQUEST DATA OBJECT
            $patient = $request->patient;
            Validator::make(['patient' => $patient], [
                'patient' => 'required|array',
                'patient.hospital_id' => 'required|string|max:255',
                'patient.name' => 'required|string|max:255',
                'patient.type' => 'required',
                'patient.station' => 'nullable|string|max:255',
                'patient.urgency' => 'nullable|string|max:20',
                'patient.remarks' => 'nullable|string',
            ])->validate();

            if ($patient['type'] == "7") {
                return response(['code' => 500, 'message' => 'Prescription type Switch Medication is currently disabled']);
            }

            $indication_use_param = $request->indication_use;
            $microbiologicals = PrescriptionWorkflow::microbiologicalRows($request->microbiologicals);
            if ($patient['type'] == "1" || $patient['type'] == "3" || $patient['type'] == "4") {
                return response(['code' => 500, 'message' => 'Prescription type are now updated to Restricted,Monitored and Non-Monitored']);
            }

            if ($patient['type'] == "2") {
                //CHECK IF INDICATION USE IS NOT EMPIRIC
                if (($indication_use_param['description'] ?? '') == "Definitive" && count($microbiologicals) < 1) {
                    return response(['code' => 500, 'message' => 'Microbiological Results is mandatory when Indication for use is Definitive']);
                }
            }

            #TYPE 6 (CANCELLATION) CARRIES NO MEDICINES
            $medications = ($patient['type'] == "6") ? [] : PrescriptionWorkflow::medicineRows($request->medications);

            DB::transaction(function () use ($request, $patient, $indication_use_param, $microbiologicals, $medications) {
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
                $prescription->outcome = $patient['outcome'];
                $prescription->diagnosis = $patient['diagnosis'];
                $prescription->for_extension = $patient['for_extension'];
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

                    $icc = new IccReview();
                    $icc->prescription_id = $prescription_id;
                    $icc->save();

                    $pharmacy = new PharmacyReview();
                    $pharmacy->prescription_id = $prescription_id;
                    $pharmacy->save();

                    PrescriptionWorkflow::log($prescription_id, "DOCTOR", "IPCC,NURSE,PHARMACY", $patient['remarks']);
                    return;
                }

                if ($patient['type'] != "1") {
                    if ($patient['type'] == "2") {
                        #INSERT INDICATION USE
                        $indication = new IndicationUse;
                        $indication->prescription_id = $prescription_id;
                        $indication->description = $indication_use_param['description'];
                        $indication->save();
                    } else if ($patient['type'] == "4" || $patient['type'] == "7") {
                        #UPDATE FOR SWITCH ANG PREVIOUS CONCURRENT
                        $intervention_param = $request->intervention;
                        if (($intervention_param['concurrent_id'] ?? "0") != "0") {
                            $selected_concurrent_id = ConcurrentTherapy::find($intervention_param['concurrent_id']);
                            if ($selected_concurrent_id) {
                                $selected_concurrent_id->for_switch = $prescription_id;
                                $selected_concurrent_id->save();
                            }
                        }
                    }

                    #INSERT MICROBIOLOGICAL
                    if ($patient['type'] != '4') {
                        foreach ($microbiologicals as $i => $row) {
                            $microbiologicals[$i]['prescription_id'] = $prescription_id;
                        }
                        MicrobiologicalResult::insert($microbiologicals);
                    }

                    ConcurrentTherapy::insert(PrescriptionWorkflow::manualConcurrentRows($request->concurrent_therapy, $patient['hospital_id'], $prescription_id));
                }

                $medicines = [];
                foreach ($medications as $row) {
                    $medicines[] = PrescriptionWorkflow::createMedicine($prescription, $row);
                }

                $released_to = "PHARMACY";
                if (PrescriptionWorkflow::skipsIcc($patient['type'], $patient['for_extension'])) {
                    foreach ($medicines as $medicine) {
                        PrescriptionWorkflow::approveMedicine($prescription, $medicine, PrescriptionWorkflow::AUTO_APPROVED_DAYS, null);
                    }
                    $pharmacy = new PharmacyReview();
                    $pharmacy->prescription_id = $prescription_id;
                    $pharmacy->save();
                } else {
                    $released_to = "IPCC";
                    $icc = new IccReview();
                    $icc->prescription_id = $prescription_id;
                    $icc->save();
                }

                PrescriptionWorkflow::refreshReviewStatus($prescription_id);
                PrescriptionWorkflow::log($prescription_id, "DOCTOR", $released_to, $patient['remarks']);
            });

            return response(['code' => 200, 'message' => 'Prescribe successfully']);
        } catch (ValidationException $exception) {
            return response(['code' => 500, 'message' => collect($exception->errors())->flatten()->first()]);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'insertPrescription', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => "Something went wrong"]);
        }
    }

    /**
     * IPCC decides each pending medicine on its own: approve for its own number of days, or reject
     * with a reason. Medicines left out of the request stay pending, and the Rx stays in the IPCC
     * queue until none are pending. Approved medicines go on to Pharmacy and rejected ones back to
     * the doctor, both at once if the decisions are mixed.
     */
    public function decidePrescriptionMedications(Request $request)
    {
        try {
            if (PrescriptionWorkflow::originForCurrentUser() !== "ICC") {
                return response(['code' => 500, 'message' => 'Permission denied']);
            }

            Validator::make($request->all(), [
                'id' => 'required|integer',
                'outcome' => 'nullable|string|max:255',
                'medications' => 'required|array|min:1',
                'medications.*.id' => 'required|integer|distinct',
                'medications.*.decision' => 'required|in:approved,rejected',
                'medications.*.remarks' => 'nullable|string|max:2000',
            ], [
                'medications.required' => 'Decide on at least one medicine',
            ])->validate();

            foreach ($request->medications as $decision) {
                if ($decision['decision'] == 'rejected' && trim($decision['remarks'] ?? '') === '') {
                    return response(['code' => 500, 'message' => 'A rejection reason is required for every rejected medicine']);
                }
                $days = $decision['days_approved'] ?? null;
                if ($decision['decision'] == 'approved' && (filter_var($days, FILTER_VALIDATE_INT) === false || (int)$days < 1)) {
                    return response(['code' => 500, 'message' => 'Days approved must be a whole number of at least 1 for every approved medicine']);
                }
            }

            $result = DB::transaction(function () use ($request) {
                $review = IccReview::where('prescription_id', $request->id)->lockForUpdate()->first();
                if (!$review) {
                    return ['code' => 500, 'message' => 'Prescription is no longer waiting for IPCC review, kindly refresh'];
                }

                $prescription = Prescription::lockForUpdate()->find($request->id);
                if ($prescription->type == "6") {
                    return ['code' => 500, 'message' => 'Cancellation requests are handled with Cancel pending'];
                }

                $ids = array_column($request->medications, 'id');
                $medicines = PrescribedMedicine::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
                foreach ($ids as $medicine_id) {
                    $medicine = $medicines->get($medicine_id);
                    if (!$medicine || $medicine->prescription_id != $prescription->id) {
                        return ['code' => 500, 'message' => 'One or more medicines do not belong to this prescription'];
                    }
                    if ($medicine->status != PrescribedMedicine::STATUS_PENDING) {
                        return ['code' => 500, 'message' => 'One or more medicines were already decided. Please refresh and try again.'];
                    }
                }

                $has_approved = false;
                $has_rejected = false;
                foreach ($request->medications as $decision) {
                    $medicine = $medicines->get($decision['id']);
                    $remarks = trim($decision['remarks'] ?? '');
                    if ($decision['decision'] == 'approved') {
                        $has_approved = true;
                        PrescriptionWorkflow::approveMedicine($prescription, $medicine, (int)$decision['days_approved'], Auth::id(), $remarks === '' ? null : $remarks);
                        PrescriptionWorkflow::log($prescription->id, "ICC", "PHARMACY",
                            $medicine->description . " · " . $decision['days_approved'] . " day(s)" . ($remarks === '' ? '' : " · " . $remarks), 'approved', $medicine->id);
                    } else {
                        $has_rejected = true;
                        PrescriptionWorkflow::rejectMedicine($medicine, $remarks);
                        PrescriptionWorkflow::log($prescription->id, "ICC", "DOCTOR", $medicine->description . " · " . $remarks, 'rejected', $medicine->id);
                    }
                }

                #INTERVENTION / SWITCH: STOP THE SWITCHED THERAPY TODAY, ONCE PER SUBMISSION
                if ($has_approved && ($prescription->type == "4" || $prescription->type == "7")) {
                    $switched = ConcurrentTherapy::where("for_switch", $prescription->id)->first();
                    if (!$switched) {
                        throw new \RuntimeException('You did not specify the antibiotic you wish to be switched');
                    }
                    $switched->stop_date = Carbon::now();
                    $switched->save();
                }

                if ($has_approved) {
                    PharmacyReview::firstOrCreate(['prescription_id' => $prescription->id]);
                }

                if ($has_rejected) {
                    if ($request->outcome) {
                        $prescription->outcome = $request->outcome;
                        $prescription->save();
                    }
                    ReturnedPrescription::firstOrCreate(['prescription_id' => $prescription->id]);
                }

                $still_pending = PrescribedMedicine::where('prescription_id', $prescription->id)
                    ->where('status', PrescribedMedicine::STATUS_PENDING)->exists();
                if (!$still_pending) {
                    $review->forceDelete();
                }

                $review_status = PrescriptionWorkflow::refreshReviewStatus($prescription->id);
                return [
                    'code' => 200,
                    'message' => 'Decisions saved',
                    'id' => $prescription->physician_id,
                    'has_approved' => $has_approved,
                    'has_rejected' => $has_rejected,
                    'review_status' => $review_status,
                ];
            });

            return response($result);
        } catch (ValidationException $exception) {
            return response(['code' => 500, 'message' => collect($exception->errors())->flatten()->first()]);
        } catch (\RuntimeException $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'decidePrescriptionMedications', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => "Something went wrong"]);
        }
    }

    /**
     * Nurse confirms receipt of what the pharmacy dispensed. IPCC approval goes through
     * decidePrescriptionMedications, one medicine at a time.
     */
    public function approvePrescription(Request $request)
    {
        try {
            if (PrescriptionWorkflow::originForCurrentUser() !== "NURSE") {
                return response(['code' => 500, 'message' => 'Permission denied']);
            }

            $result = DB::transaction(function () use ($request) {
                $review = NurseReview::where('prescription_id', $request->id)->lockForUpdate()->first();
                if (!$review) {
                    return ['code' => 500, 'message' => 'Prescription dont exists, kindly refresh'];
                }

                #A SPLIT DECISION CAN BRING THE SAME RX BACK FOR A SECOND DISPENSING CYCLE
                $rendered = RenderedPrescription::firstOrNew(['prescription_id' => $request->id]);
                $rendered->updated_at = Carbon::now();
                $rendered->save();

                PrescriptionWorkflow::log($request->id, "NURSE", "END CYCLE", $request->remarks, 'approved');
                $review->forceDelete();
                return ['code' => 200, 'message' => 'Prescription approved'];
            });

            return response($result);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'approvePrescription', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => "Something went wrong"]);
        }
    }

    /**
     * Returns medicines to the doctor. Pharmacy returns the approved medicines it hasn't dispensed;
     * Nurse returns the ones dispensed since the ward last received. Medicines outside that step are
     * left alone. IPCC rejects individual medicines through decidePrescriptionMedications, and only
     * uses this for type 6 cancellations.
     */
    public function rejectPrescription(Request $request)
    {
        try {
            $origin = PrescriptionWorkflow::originForCurrentUser();
            if (!$origin) {
                return response(['code' => 500, 'message' => 'Permission denied']);
            }

            $result = DB::transaction(function () use ($request, $origin) {
                $prescription_review = self::queueRow($origin, $request->id);
                if (!$prescription_review) {
                    return ['code' => 500, 'message' => 'Prescription dont exists'];
                }

                $current_prescription = Prescription::lockForUpdate()->find($request->id);

                if ($current_prescription->type == "6") {
                    return self::cancelPendingPrescriptions($origin, $current_prescription, $prescription_review, $request->remarks);
                }

                if ($origin == "ICC") {
                    return ['code' => 500, 'message' => 'Decide on each medicine instead'];
                }

                $reason = trim($request->remarks ?? '');
                if ($reason === '') {
                    return ['code' => 500, 'message' => 'Please give a reason for returning the prescription'];
                }

                if ($request->outcome != null) {
                    $current_prescription->outcome = $request->outcome;
                    $current_prescription->save();
                }

                foreach (self::medicinesInStep($origin, $current_prescription->id) as $medicine) {
                    PrescriptionWorkflow::rejectMedicine($medicine, $reason);
                }

                #INTERVENTION / SWITCH: RESTORE THE SWITCHED THERAPY'S OWN STOP DATE
                if ($current_prescription->type == "4" || $current_prescription->type == "7") {
                    $switched_concurrent = ConcurrentTherapy::where('for_switch', $current_prescription->id)->first();
                    if ($switched_concurrent) {
                        $date = Carbon::parse($switched_concurrent->start_date)->addDays($switched_concurrent->days_approved - 1);
                        $switched_concurrent->stop_date = $date->format('Y-m-d');
                        $switched_concurrent->save();
                    }
                }

                ReturnedPrescription::firstOrCreate(['prescription_id' => $current_prescription->id]);
                PrescriptionWorkflow::log($current_prescription->id, $origin, "DOCTOR", $reason, 'rejected');

                #AFTER RETURNED DELETE RECORD
                $prescription_review->forceDelete();
                PrescriptionWorkflow::refreshReviewStatus($current_prescription->id);
                return ['code' => 200, 'message' => 'Prescription returned', 'id' => $current_prescription->physician_id];
            });

            return response($result);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'rejectPrescription', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => "Something went wrong"]);
        }
    }

    private static function queueRow($origin, $prescription_id)
    {
        $model = ['NURSE' => NurseReview::class, 'ICC' => IccReview::class, 'PHARMACY' => PharmacyReview::class][$origin];
        return $model::where('prescription_id', $prescription_id)->lockForUpdate()->first();
    }

    /**
     * The approved medicines that are currently at this step: not yet dispensed for Pharmacy;
     * dispensed since the ward last received for Nurse.
     */
    public static function medicinesInStep($origin, $prescription_id)
    {
        $query = PrescribedMedicine::where('prescription_id', $prescription_id)
            ->where('status', PrescribedMedicine::STATUS_APPROVED)
            ->lockForUpdate();

        if ($origin == "PHARMACY") {
            return $query->whereNull('date_rendered')->get();
        }

        $last_received = RenderedPrescription::where('prescription_id', $prescription_id)->value('updated_at');
        $query->whereNotNull('date_rendered');
        if ($last_received) {
            $query->where('date_rendered', '>', $last_received);
        }
        return $query->get();
    }

    /**
     * Type 6: cancels every earlier pending Rx of this patient by this physician at the same stage,
     * and stops their running therapies. Unchanged behaviour, now inside the caller's transaction.
     */
    private static function cancelPendingPrescriptions($origin, Prescription $current_prescrption, $prescription_review, $remarks)
    {
        $queue_table = ['NURSE' => 'nurse_reviews', 'ICC' => 'icc_reviews', 'PHARMACY' => 'pharmacy_reviews'][$origin];
        $pending_prescriptions = DB::table('paperless_prescription.' . $queue_table . ' as n')
            ->join('paperless_prescription.prescriptions as p', 'n.prescription_id', '=', 'p.id')
            ->where('p.created_at', '<=', $current_prescrption->created_at)
            ->where('p.physician_id', '=', $current_prescrption->physician_id)
            ->where('p.hospital_id', '=', $current_prescrption->hospital_id)
            ->where('p.type', '!=', '6')
            ->get(['n.id', 'n.prescription_id']);

        #GET ALL ANTIBIOTIC AND UPDATE STOP DATE INTO DATE PRESCRIBED
        $stop_date = date('Y-m-d', strtotime($current_prescrption->created_at));
        $concurrent_therapies = DB::table('paperless_prescription.concurrent_therapy as c')
            ->join('paperless_prescription.prescriptions as p', 'c.prescription_id', '=', 'p.id')
            ->where('p.hospital_id', '=', $current_prescrption->hospital_id)
            ->where('p.physician_id', '=', $current_prescrption->physician_id)
            ->where('c.stop_date', '>', $stop_date)
            ->get(['c.id']);

        foreach ($concurrent_therapies as $therapy) {
            $current_therapy = ConcurrentTherapy::find($therapy->id);
            $current_therapy->stop_date = $stop_date;
            $current_therapy->save();
        }

        foreach ($pending_prescriptions as $pending) {
            ReturnedPrescription::firstOrCreate(['prescription_id' => $pending->prescription_id]);
            PrescriptionWorkflow::log($pending->prescription_id, $origin, "DOCTOR", $remarks, 'rejected');
            DB::table($queue_table)->where('id', $pending->id)->delete();
        }

        $prescription_review->forceDelete();
        return ['code' => 200, 'message' => 'All Prescription returned', 'id' => $current_prescrption->physician_id];
    }

    /**
     * "Delete Rx" from the doctor's Returned list: withdraws the rejected medicines. The Rx only
     * ends (status 0) when none of its medicines is still approved and active.
     */
    public function deletePrescription(Request $request)
    {
        try {
            $result = DB::transaction(function () use ($request) {
                $returned_prescription = ReturnedPrescription::where('prescription_id', $request->id)->lockForUpdate()->first();
                if (!$returned_prescription) {
                    return ['code' => 500, 'message' => 'Prescription ID dont exists'];
                }

                $prescription = Prescription::lockForUpdate()->find($request->id);
                if ($prescription->physician_id != Auth::id()) {
                    return ['code' => 500, 'message' => 'Permission denied'];
                }

                PrescribedMedicine::where('prescription_id', $prescription->id)
                    ->where('status', PrescribedMedicine::STATUS_REJECTED)
                    ->update(['status' => PrescribedMedicine::STATUS_WITHDRAWN]);

                $has_approved = PrescribedMedicine::where('prescription_id', $prescription->id)
                    ->where('status', PrescribedMedicine::STATUS_APPROVED)->exists();
                if (!$has_approved) {
                    $prescription->status = 0;
                    $prescription->save();
                }

                PrescriptionWorkflow::log($prescription->id, "Doctor", $has_approved ? "Withdrawn" : "Cycle End", $request->remarks, 'cycle_end');
                $returned_prescription->forceDelete();
                PrescriptionWorkflow::refreshReviewStatus($prescription->id);
                return ['code' => 200, 'message' => $has_approved ? 'Rejected medicines withdrawn' : 'Prescription deleted'];
            });

            return response($result);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'deletePrescription', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => "Something went wrong"]);
        }
    }

    public function returnMeds(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
                'amount' => 'required|integer|min:1',
                'remarks' => 'nullable|string|max:2000',
            ]);
            if ($validator->fails()) {
                return response(['code' => 500, 'message' => $validator->errors()->first()]);
            }

            $result = DB::transaction(function () use ($request) {
                #CHECK IF CURRENTLY ON HAND IS GREATER THAN 0;
                $prescribed_meds = PrescribedMedicine::lockForUpdate()->find($request->id);
                if (!$prescribed_meds) {
                    return ['code' => 500, 'message' => 'Prescribed medicine not found'];
                }

                if ($prescribed_meds->quantity_rendered < ($prescribed_meds->quantity_returned + $request->amount)) {
                    return ['code' => 500, 'message' => 'Prescribed medicine on hand is less than 1'];
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
                return ['code' => 200, 'message' => 'Successfully returned'];
            });

            return response($result);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'returnMeds', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => "Something went wrong"]);
        }
    }

    /**
     * @return array|null [from, to] as Y-m-d, or null when no valid range was given
     */
    private static function dateRange(Request $request)
    {
        $from = $request->from_date;
        $to = $request->to_date;
        if (!$from || !$to) {
            return null;
        }
        $valid = function ($date) {
            $parsed = \DateTime::createFromFormat('Y-m-d', $date);
            return $parsed && $parsed->format('Y-m-d') === $date;
        };
        return ($valid($from) && $valid($to)) ? [$from, $to] : null;
    }

    public function getAmsSummary(Request $request)
    {
        try {
            $patient = $request->filter_patient;
            $description = $request->filter_description;
            $type = $request->filter_type;
            $range = self::dateRange($request);

            $ams_summary = DB::table('paperless_prescription.concurrent_therapy as c')
                ->select(DB::raw('ph.name,TIMESTAMPDIFF(YEAR, ph.birthdate, CURDATE()) as age,ph.birthdate,ph.sex,p.station,p.created_at,CONCAT(u.fname," ",u.mname," ",u.lname) as physician,
                c.antimicrobial,CONCAT(a.fname," ",a.mname," ",a.lname) as approved_by,c.days_approved,c.start_date,c.stop_date,c.date_approved,p.attending_physician'))
                ->join('paperless_prescription.patient_history as ph', 'c.prescription_id', '=', 'ph.prescription_id')
                ->join('paperless_prescription.prescriptions as p', 'c.prescription_id', '=', 'p.id')
                ->join('tdh_user.users as u', 'p.physician_id', '=', 'u.id')
                ->leftJoin('tdh_user.users as a', 'c.approved_by', '=', 'a.id')
                ->where(function ($query) use ($patient, $description, $range, $type) {
                    #COMMENT THIS IF YOU WANT TO INCLUDED MANUALLY ADDED CONCURRENT ANTIBIOTIC THERAPY
                    $query->whereNotNull('c.date_approved');

                    if ($type != "") {
                        $query->where('p.type', '=', $type);
                    }

                    if ($patient != "") {
                        $query->where('ph.name', 'LIKE', '%' . $patient . '%');
                    }

                    if ($description != "") {
                        $query->where('antimicrobial', 'LIKE', '%' . $description . '%');
                    }

                    if ($range) {
                        $query->where('c.start_date', '>=', $range[0])->where('c.stop_date', '<=', $range[1]);
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
            $range = self::dateRange($request);

            $monitored_summary = DB::table('paperless_prescription.prescriptions as p')
                ->select(DB::raw('ph.name,TIMESTAMPDIFF(YEAR, ph.birthdate, CURDATE()) as age,ph.birthdate,ph.sex,p.station,p.created_at,
                CONCAT(u.fname," ",u.mname," ",u.lname) as physician,c.description,
                COALESCE(c.quantity_rendered,"") as quantity_rendered,COALESCE(c.quantity_returned,"") as quantity_returned'))
                ->join('paperless_prescription.patient_history as ph', 'p.id', '=', 'ph.prescription_id')
                ->join('paperless_prescription.prescribed_medicines as c', 'c.prescription_id', '=', 'p.id')
                ->join('tdh_user.users as u', 'p.physician_id', '=', 'u.id')
                ->where(function ($query) use ($patient, $description, $range) {
                    #ONLY MEDICINES THE PHARMACY HAS DISPENSED
                    $query->whereNotNull('c.date_rendered');
                    #UNCOMMENT THIS IF YOU DONT WANT TO INCLUDE MONITORED AMS

                    $query->where('p.type', '<>', '5');
                    if ($patient) {
                        $query->where('ph.name', 'LIKE', '%' . $patient . '%');
                    }
                    if ($description) {
                        $query->where('c.description', 'LIKE', '%' . $description . '%');
                    }
                    if ($range) {
                        $query->whereBetween(DB::raw('DATE(p.created_at)'), $range);
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
