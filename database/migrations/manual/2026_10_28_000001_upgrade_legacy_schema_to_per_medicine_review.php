<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-shot upgrade of an imported legacy database to the per-medicine review schema.
 * Combines 2026_10_27_000001 to 000004 and also links prescription_logs to their medicine.
 *
 * Lives outside database/migrations so a plain `php artisan migrate` never picks it up. Run it with:
 *   php artisan migrate --path=database/migrations/manual/2026_10_28_000001_upgrade_legacy_schema_to_per_medicine_review.php
 *
 * Every step skips what already exists, so it is safe to re-run. Legacy data has exactly one
 * medicine per prescription, so prescription_id maps to a single prescribed_medicines.id.
 */
class UpgradeLegacySchemaToPerMedicineReview extends Migration
{
    const COMBINED = [
        '2026_10_27_000001_add_per_medicine_review_columns_to_prescribed_medicines_table',
        '2026_10_27_000002_add_review_status_to_prescriptions_table',
        '2026_10_27_000003_link_concurrent_therapy_and_logs_to_prescribed_medicines',
        '2026_10_27_000004_backfill_per_medicine_review_data',
    ];

    /**
     * @return void
     */
    public function up()
    {
        $this->addColumns();
        $this->backfill();
        $this->markCombinedAsRun();
    }

    private function addColumns()
    {
        $missing = function ($table, $column) {
            return !Schema::hasColumn($table, $column);
        };

        Schema::table('prescribed_medicines', function (Blueprint $table) use ($missing) {
            if ($missing('prescribed_medicines', 'status')) {
                //0 = pending, 1 = approved, 2 = rejected, 3 = withdrawn
                $table->tinyInteger('status')->default(0)->after('quantity_returned');
            }
            if ($missing('prescribed_medicines', 'days_requested')) {
                $table->integer('days_requested')->nullable()->after('frequency');
            }
            if ($missing('prescribed_medicines', 'days_approved')) {
                $table->integer('days_approved')->nullable()->after('status');
            }
            if ($missing('prescribed_medicines', 'decision_remarks')) {
                $table->text('decision_remarks')->nullable()->after('days_approved');
            }
            if ($missing('prescribed_medicines', 'decided_by')) {
                $table->integer('decided_by')->nullable()->after('decision_remarks');
            }
            if ($missing('prescribed_medicines', 'decided_at')) {
                $table->dateTime('decided_at')->nullable()->after('decided_by');
            }
        });

        $has_index = DB::select("SHOW INDEX FROM prescribed_medicines WHERE Key_name = 'prescribed_medicines_prescription_id_status_index'");
        if (!$has_index) {
            Schema::table('prescribed_medicines', function (Blueprint $table) {
                $table->index(['prescription_id', 'status']);
            });
        }

        if ($missing('prescriptions', 'review_status')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                //0 = pending, 1 = partially approved, 2 = approved, 3 = rejected; null = no medicines
                $table->tinyInteger('review_status')->nullable()->after('status');
            });
        }

        foreach (['concurrent_therapy', 'prescription_logs'] as $name) {
            if ($missing($name, 'prescribed_medicine_id')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->unsignedBigInteger('prescribed_medicine_id')->nullable()->after('prescription_id');
                    $table->foreign('prescribed_medicine_id')->references('id')->on('prescribed_medicines')->nullOnDelete();
                });
            }
        }
    }

    private function backfill()
    {
        DB::transaction(function () {
            DB::statement('UPDATE prescribed_medicines m JOIN prescriptions p ON p.id = m.prescription_id
                SET m.days_requested = p.days_requested WHERE m.days_requested IS NULL');

            #STATUS FROM THE RX'S POSITION IN THE WORKFLOW: APPROVED BY DEFAULT, THEN REJECTED, THEN PENDING (ICC)
            DB::statement('UPDATE prescribed_medicines SET status = 1 WHERE decided_at IS NULL');

            DB::statement('UPDATE prescribed_medicines m JOIN prescriptions p ON p.id = m.prescription_id
                SET m.status = 2
                WHERE m.decided_at IS NULL
                  AND (p.status = 0 OR EXISTS (SELECT 1 FROM returned_prescriptions r WHERE r.prescription_id = m.prescription_id))');

            DB::statement('UPDATE prescribed_medicines m SET m.status = 0
                WHERE m.decided_at IS NULL
                  AND EXISTS (SELECT 1 FROM icc_reviews i WHERE i.prescription_id = m.prescription_id)');

            #ONE MEDICINE PER LEGACY RX, SO EVERY ROW LINKS TO THE MEDICINE OF ITS RX
            DB::statement('UPDATE concurrent_therapy c JOIN prescribed_medicines m ON m.prescription_id = c.prescription_id
                SET c.prescribed_medicine_id = m.id WHERE c.prescribed_medicine_id IS NULL');

            DB::statement('UPDATE prescription_logs l JOIN prescribed_medicines m ON m.prescription_id = l.prescription_id
                SET l.prescribed_medicine_id = m.id WHERE l.prescribed_medicine_id IS NULL');

            #APPROVAL DETAILS FROM THE LATEST APPROVAL ROW OF THE RX
            DB::statement('UPDATE prescribed_medicines m
                JOIN (SELECT prescription_id, MAX(id) AS id FROM concurrent_therapy
                      WHERE date_approved IS NOT NULL GROUP BY prescription_id) latest ON latest.prescription_id = m.prescription_id
                JOIN concurrent_therapy c ON c.id = latest.id
                SET m.days_approved = c.days_approved, m.decided_by = c.approved_by, m.decided_at = c.date_approved
                WHERE m.status = 1 AND m.decided_at IS NULL');

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
     * Records the four separate migrations as already run so a later `php artisan migrate`
     * does not try to add the same columns again. They go in the previous batch so that
     * rolling back this migration does not also try to roll them back.
     */
    private function markCombinedAsRun()
    {
        $migrations = DB::table(config('database.migrations'));
        $batch = (int)$migrations->max('batch');
        $ran = $migrations->whereIn('migration', self::COMBINED)->pluck('migration')->all();

        foreach (array_diff(self::COMBINED, $ran) as $name) {
            DB::table(config('database.migrations'))->insert(['migration' => $name, 'batch' => $batch]);
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        foreach (['prescription_logs', 'concurrent_therapy'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropForeign(['prescribed_medicine_id']);
                $table->dropColumn('prescribed_medicine_id');
            });
        }

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn('review_status');
        });

        Schema::table('prescribed_medicines', function (Blueprint $table) {
            $table->dropIndex(['prescription_id', 'status']);
            $table->dropColumn(['days_requested', 'days_approved', 'decision_remarks', 'decided_by', 'decided_at']);
        });

        DB::table(config('database.migrations'))->whereIn('migration', self::COMBINED)->delete();
    }
}
