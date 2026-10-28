<?php

namespace App\Services;

use App\Models\ConcurrentTherapy;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use App\Models\PrescriptionLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Per-medicine rules shared by the prescribing, IPCC, pharmacy and nurse controllers.
 * Each medicine carries its own decision; the Rx's review_status is only ever derived from them.
 */
class PrescriptionWorkflow
{
    #MONITORED / NON-MONITORED SKIP IPCC AND EVERY MEDICINE IS APPROVED FOR THIS MANY DAYS
    const AUTO_APPROVED_DAYS = 7;

    #ROLE -> WORKFLOW STAGE. THE STAGE IS NEVER TAKEN FROM THE REQUEST.
    const ROLE_ORIGINS = [
        'ICC' => 'ICC',
        'Nurse' => 'NURSE',
        'Nurse Admin' => 'NURSE',
        'Pharmacist' => 'PHARMACY',
        'Pharmacist Admin' => 'PHARMACY',
    ];

    public static function originForCurrentUser()
    {
        return self::ROLE_ORIGINS[session('access_rights')] ?? null;
    }

    public static function skipsIcc($type, $for_extension)
    {
        return in_array((string)$type, ['1', '5', '8'], true) && (string)$for_extension !== '1';
    }

    /**
     * Validates submitted medicine rows and keeps only the fields a prescriber may set,
     * so a request can never write quantity_rendered, status, or another Rx's id.
     *
     * @throws ValidationException
     */
    public static function medicineRows($medications)
    {
        $validator = Validator::make(['medications' => $medications], [
            'medications' => 'required|array|min:1',
            'medications.*.id' => 'nullable|integer',
            'medications.*.description' => 'required|string|max:255',
            'medications.*.unit' => 'nullable|string|max:255',
            'medications.*.dosage_strength' => 'required|string|max:100',
            'medications.*.dosage_unit' => 'required|string|max:100',
            'medications.*.frequency' => 'required|string|max:100',
            #DAYS ARE SET ON APPROVAL, NOT BY THE PRESCRIBER; KEPT OPTIONAL FOR OLDER CLIENTS
            'medications.*.days_requested' => 'nullable|integer|min:1',
        ], [
            'medications.required' => 'There must be a medication',
        ]);
        $validator->validate();

        return array_map(function ($medication) {
            return [
                'id' => $medication['id'] ?? null,
                'description' => $medication['description'],
                'unit' => $medication['unit'] ?? null,
                'dosage_strength' => $medication['dosage_strength'],
                'dosage_unit' => $medication['dosage_unit'],
                'frequency' => $medication['frequency'],
                'quantity_ordered' => 0,
                'days_requested' => isset($medication['days_requested']) ? (int)$medication['days_requested'] : null,
            ];
        }, array_values($medications));
    }

    /**
     * @throws ValidationException
     */
    public static function microbiologicalRows($microbiologicals)
    {
        Validator::make(['rows' => $microbiologicals ?? []], [
            'rows' => 'array',
            'rows.*.date' => 'required|date',
            'rows.*.specimen' => 'required|string|max:255',
            'rows.*.pathogen' => 'required|string',
        ])->validate();

        return array_map(function ($row) {
            return ['date' => $row['date'], 'specimen' => $row['specimen'], 'pathogen' => $row['pathogen']];
        }, array_values($microbiologicals ?? []));
    }

    /**
     * Concurrent therapies the doctor typed in by hand (never approvals).
     *
     * @throws ValidationException
     */
    public static function manualConcurrentRows($rows, $hospital_id, $prescription_id)
    {
        Validator::make(['rows' => $rows ?? []], [
            'rows' => 'array',
            'rows.*.concurrent_date' => 'required|date',
            'rows.*.antimicrobial' => 'required|string|max:255',
            'rows.*.dosing_regimen' => 'nullable|string|max:255',
            'rows.*.start_date' => 'required|date',
            'rows.*.stop_date' => 'required|date',
            'rows.*.days_approved' => 'nullable|integer',
        ])->validate();

        return array_map(function ($row) use ($hospital_id, $prescription_id) {
            return [
                'prescription_id' => $prescription_id,
                'hospital_id' => $hospital_id,
                'concurrent_date' => $row['concurrent_date'],
                'antimicrobial' => $row['antimicrobial'],
                'dosing_regimen' => $row['dosing_regimen'] ?? null,
                'start_date' => $row['start_date'],
                'stop_date' => $row['stop_date'],
                'days_approved' => $row['days_approved'] ?? null,
                'for_switch' => (($row['for_switch'] ?? '0') == '1') ? $prescription_id : null,
            ];
        }, array_values($rows ?? []));
    }

    public static function createMedicine(Prescription $prescription, array $row)
    {
        $medicine = new PrescribedMedicine;
        $medicine->prescription_id = $prescription->id;
        $medicine->description = $row['description'];
        $medicine->unit = $row['unit'];
        $medicine->dosage_strength = $row['dosage_strength'];
        $medicine->dosage_unit = $row['dosage_unit'];
        $medicine->frequency = $row['frequency'];
        $medicine->quantity_ordered = $row['quantity_ordered'];
        $medicine->days_requested = $row['days_requested'];
        $medicine->status = PrescribedMedicine::STATUS_PENDING;
        $medicine->save();
        return $medicine;
    }

    /**
     * Approves one medicine and writes its linked concurrent_therapy row (what the AMS summary reads).
     * $approved_by is null for automatic approvals, as before.
     */
    public static function approveMedicine(Prescription $prescription, PrescribedMedicine $medicine, $days, $approved_by, $note = null)
    {
        $now = Carbon::now();
        $medicine->status = PrescribedMedicine::STATUS_APPROVED;
        $medicine->days_approved = $days;
        $medicine->decision_remarks = $note;
        $medicine->decided_by = $approved_by;
        $medicine->decided_at = $now;
        $medicine->save();

        $concurrent = new ConcurrentTherapy;
        $concurrent->prescription_id = $prescription->id;
        $concurrent->prescribed_medicine_id = $medicine->id;
        $concurrent->hospital_id = $prescription->hospital_id;
        $concurrent->concurrent_date = $now;
        $concurrent->antimicrobial = $medicine->description;
        $concurrent->dosing_regimen = $medicine->dosage_strength . '' . $medicine->dosage_unit . ' ' . $medicine->frequency;
        $concurrent->start_date = $now;
        $concurrent->stop_date = $now->copy()->addDays($days - 1)->format('Y-m-d');
        $concurrent->days_approved = $days;
        $concurrent->approved_by = $approved_by;
        $concurrent->date_approved = $now;
        $concurrent->save();
    }

    /**
     * Rejects one medicine and removes its approval record, so the AMS summary no longer counts it.
     */
    public static function rejectMedicine(PrescribedMedicine $medicine, $reason)
    {
        $medicine->status = PrescribedMedicine::STATUS_REJECTED;
        $medicine->days_approved = null;
        $medicine->decision_remarks = $reason;
        $medicine->decided_by = Auth::id();
        $medicine->decided_at = Carbon::now();
        $medicine->save();

        ConcurrentTherapy::where('prescribed_medicine_id', $medicine->id)->whereNotNull('date_approved')->delete();
    }

    /**
     * Pending: nothing decided · Partially approved: a mix, or some still pending ·
     * Approved: all approved · Rejected: all rejected. Withdrawn medicines don't count;
     * an Rx whose medicines were all withdrawn counts as rejected.
     */
    public static function refreshReviewStatus($prescription_id)
    {
        $counts = PrescribedMedicine::where('prescription_id', $prescription_id)
            ->selectRaw('SUM(status = ?) AS pending, SUM(status = ?) AS approved, SUM(status = ?) AS rejected, SUM(status = ?) AS withdrawn', [
                PrescribedMedicine::STATUS_PENDING, PrescribedMedicine::STATUS_APPROVED,
                PrescribedMedicine::STATUS_REJECTED, PrescribedMedicine::STATUS_WITHDRAWN,
            ])
            ->first();

        $pending = (int)$counts->pending;
        $approved = (int)$counts->approved;
        $rejected = (int)$counts->rejected;

        if ($pending + $approved + $rejected === 0) {
            $status = ((int)$counts->withdrawn > 0) ? Prescription::REVIEW_REJECTED : null;
        } else if ($approved === 0 && $rejected === 0) {
            $status = Prescription::REVIEW_PENDING;
        } else if ($pending === 0 && $rejected === 0) {
            $status = Prescription::REVIEW_APPROVED;
        } else if ($pending === 0 && $approved === 0) {
            $status = Prescription::REVIEW_REJECTED;
        } else {
            $status = Prescription::REVIEW_PARTIALLY_APPROVED;
        }

        DB::table('prescriptions')->where('id', $prescription_id)->update(['review_status' => $status]);
        return $status;
    }

    /**
     * Writes an activity log row. User text is escaped here because the log stores HTML badges
     * and older pages append it to the DOM as-is.
     */
    public static function log($prescription_id, $origin, $released_to, $remarks, $badge = null, $medicine_id = null)
    {
        $badges = [
            'approved' => "<span class='badge bg-success text-white'>Approved <i class='bi bi-hand-thumbs-up-fill'></i></span>",
            'rejected' => "<span class='badge bg-danger text-white'>Rejected <i class='bi bi-hand-thumbs-down-fill'></i></span>",
            'cycle_end' => "<span class='badge bg-success text-white'>Cycle End <i class='bi bi-hand-thumbs-up-fill'></i></span>",
        ];

        $logs = new PrescriptionLog;
        $logs->prescription_id = $prescription_id;
        $logs->prescribed_medicine_id = $medicine_id;
        $logs->origin = $origin;
        $logs->released_by = Auth::id();
        $logs->released_to = $released_to;
        $logs->remarks = $badge ? $badges[$badge] . "<span class='ms-2'>" . e($remarks) . "</span>" : e($remarks);
        $logs->save();
    }

    /**
     * Turns a stored log remark (which may predate escaping) into plain text plus the decision
     * its badge recorded, so the browser never renders stored HTML.
     */
    public static function readableRemarks($remarks)
    {
        $decision = null;
        if (strpos($remarks ?? '', "badge bg-danger") !== false) {
            $decision = 'rejected';
        } else if (strpos($remarks ?? '', "Cycle End") !== false) {
            $decision = 'cycle_end';
        } else if (strpos($remarks ?? '', "badge bg-success") !== false) {
            $decision = 'approved';
        }

        $text = html_entity_decode(strip_tags($remarks ?? ''), ENT_QUOTES);
        $text = trim(preg_replace('/^\s*(Approved|Rejected|Cycle End)\s*/', '', $text));
        return ['decision' => $decision, 'text' => $text];
    }
}
