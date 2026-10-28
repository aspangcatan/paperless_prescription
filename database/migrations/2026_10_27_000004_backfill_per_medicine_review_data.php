<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillPerMedicineReviewData extends Migration
{
    /**
     * Gives every pre-existing medicine the decision its Rx was in. Every legacy Rx has exactly one
     * medicine, so the medicine simply inherits the Rx's position in the workflow:
     *   in icc_reviews                              -> pending
     *   in returned_prescriptions, or ended (status 0) from Returned -> rejected (reason from its log)
     *   anywhere else                               -> approved (days and approver from concurrent_therapy)
     * Plain SQL on purpose: the app's status helpers may change, a data migration must not.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            DB::statement('UPDATE prescribed_medicines m JOIN prescriptions p ON p.id = m.prescription_id
                SET m.days_requested = p.days_requested WHERE m.days_requested IS NULL');

            DB::statement('UPDATE prescribed_medicines SET status = 1 WHERE decided_at IS NULL');

            DB::statement('UPDATE prescribed_medicines m JOIN prescriptions p ON p.id = m.prescription_id
                SET m.status = 2
                WHERE m.decided_at IS NULL
                  AND (p.status = 0 OR EXISTS (SELECT 1 FROM returned_prescriptions r WHERE r.prescription_id = m.prescription_id))');

            DB::statement('UPDATE prescribed_medicines m SET m.status = 0
                WHERE m.decided_at IS NULL
                  AND EXISTS (SELECT 1 FROM icc_reviews i WHERE i.prescription_id = m.prescription_id)');

            #APPROVAL DETAILS FROM THE LATEST APPROVAL ROW OF THE RX
            DB::statement('UPDATE prescribed_medicines m
                JOIN (SELECT prescription_id, MAX(id) AS id FROM concurrent_therapy
                      WHERE date_approved IS NOT NULL GROUP BY prescription_id) latest ON latest.prescription_id = m.prescription_id
                JOIN concurrent_therapy c ON c.id = latest.id
                SET m.days_approved = c.days_approved, m.decided_by = c.approved_by, m.decided_at = c.date_approved
                WHERE m.status = 1 AND m.decided_at IS NULL');

            DB::statement('UPDATE concurrent_therapy c
                JOIN prescribed_medicines m ON m.prescription_id = c.prescription_id
                SET c.prescribed_medicine_id = m.id
                WHERE c.date_approved IS NOT NULL AND c.prescribed_medicine_id IS NULL
                  AND (SELECT COUNT(*) FROM prescribed_medicines x WHERE x.prescription_id = c.prescription_id) = 1');

            #REJECTION REASON AND TIME FROM THE LATEST LOG THAT SENT THE RX BACK TO THE DOCTOR
            $rejected = DB::table('prescribed_medicines as m')
                ->join(DB::raw("(SELECT prescription_id, MAX(id) AS id FROM prescription_logs WHERE released_to = 'DOCTOR' GROUP BY prescription_id) latest"),
                    'latest.prescription_id', '=', 'm.prescription_id')
                ->join('prescription_logs as l', 'l.id', '=', 'latest.id')
                ->where('m.status', 2)
                ->whereNull('m.decided_at')
                ->get(['m.id', 'l.remarks', 'l.released_by', 'l.date_forwarded', 'l.created_at']);

            foreach ($rejected as $row) {
                $reason = trim(preg_replace('/^\s*Rejected\s*/', '', strip_tags($row->remarks ?? '')));
                DB::table('prescribed_medicines')->where('id', $row->id)->update([
                    'decision_remarks' => $reason === '' ? null : $reason,
                    'decided_by' => $row->released_by,
                    'decided_at' => $row->date_forwarded ?? $row->created_at,
                ]);
            }

            #AGGREGATE PER RX, IGNORING WITHDRAWN MEDICINES
            DB::statement('UPDATE prescriptions p
                JOIN (SELECT prescription_id,
                             SUM(status = 0) AS pending, SUM(status = 1) AS approved, SUM(status = 2) AS rejected
                      FROM prescribed_medicines WHERE status <> 3 GROUP BY prescription_id) s ON s.prescription_id = p.id
                SET p.review_status = CASE
                    WHEN s.approved = 0 AND s.rejected = 0 THEN 0
                    WHEN s.pending = 0 AND s.rejected = 0 THEN 2
                    WHEN s.pending = 0 AND s.approved = 0 THEN 3
                    ELSE 1 END');
        });
    }

    /**
     * Data only; the column migrations' rollbacks remove what this filled in.
     *
     * @return void
     */
    public function down()
    {
    }
}
