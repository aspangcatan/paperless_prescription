<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillPrescribedMedicinesStatusForPharmacyBound extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('prescribed_medicines')
            ->where('status', 0)
            ->whereIn('prescription_id', function ($query) {
                $query->select('prescription_id')->from('pharmacy_reviews')
                    ->union(
                        DB::table('rendered_prescriptions')->select('prescription_id')
                    );
            })
            ->update(['status' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Intentionally no-op: this is a one-time data backfill, not a
        // structural change, and there's no reliable way to distinguish
        // "was already status=1 before this backfill ran" from
        // "this backfill set it" in order to revert selectively.
    }
}
