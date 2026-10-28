<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReviewStatusToPrescriptionsTable extends Migration
{
    /**
     * Aggregate of the Rx's medicine statuses, kept separate from `status` (active / cycle ended).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            //0 = pending, 1 = partially approved, 2 = approved, 3 = rejected; null = no medicines
            $table->tinyInteger('review_status')->nullable()->after('status');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn('review_status');
        });
    }
}
