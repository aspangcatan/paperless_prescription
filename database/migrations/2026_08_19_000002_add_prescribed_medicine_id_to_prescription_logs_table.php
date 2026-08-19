<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrescribedMedicineIdToPrescriptionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prescription_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('prescribed_medicine_id')->nullable()->after('prescription_id');
            $table->foreign('prescribed_medicine_id')->references('id')->on('prescribed_medicines')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prescription_logs', function (Blueprint $table) {
            $table->dropForeign(['prescribed_medicine_id']);
            $table->dropColumn('prescribed_medicine_id');
        });
    }
}
