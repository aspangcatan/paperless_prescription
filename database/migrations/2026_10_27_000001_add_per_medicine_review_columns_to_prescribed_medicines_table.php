<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerMedicineReviewColumnsToPrescribedMedicinesTable extends Migration
{
    /**
     * Production already has `status` from the reverted 2026-08-19 migration, so it is only added
     * when missing. Rollback never drops `status`: it may predate this migration and hold real data.
     *
     * @return void
     */
    public function up()
    {
        $add_status = !Schema::hasColumn('prescribed_medicines', 'status');

        Schema::table('prescribed_medicines', function (Blueprint $table) use ($add_status) {
            if ($add_status) {
                //0 = pending, 1 = approved, 2 = rejected, 3 = withdrawn
                $table->tinyInteger('status')->default(0)->after('quantity_returned');
            }
            $table->integer('days_requested')->nullable()->after('frequency');
            $table->integer('days_approved')->nullable()->after('status');
            $table->text('decision_remarks')->nullable()->after('days_approved');
            $table->integer('decided_by')->nullable()->after('decision_remarks');
            $table->dateTime('decided_at')->nullable()->after('decided_by');
            $table->index(['prescription_id', 'status']);
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('prescribed_medicines', function (Blueprint $table) {
            $table->dropIndex(['prescription_id', 'status']);
            $table->dropColumn(['days_requested', 'days_approved', 'decision_remarks', 'decided_by', 'decided_at']);
        });
    }
}
