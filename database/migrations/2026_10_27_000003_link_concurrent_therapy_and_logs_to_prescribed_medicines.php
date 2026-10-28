<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LinkConcurrentTherapyAndLogsToPrescribedMedicines extends Migration
{
    /**
     * Lets an approval (concurrent_therapy) and a decision log point at the exact medicine,
     * instead of matching on description text. Null for manual concurrent entries and Rx-level logs.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('concurrent_therapy', function (Blueprint $table) {
            $table->unsignedBigInteger('prescribed_medicine_id')->nullable()->after('prescription_id');
            $table->foreign('prescribed_medicine_id')->references('id')->on('prescribed_medicines')->nullOnDelete();
        });

        Schema::table('prescription_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('prescribed_medicine_id')->nullable()->after('prescription_id');
            $table->foreign('prescribed_medicine_id')->references('id')->on('prescribed_medicines')->nullOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('prescription_logs', function (Blueprint $table) {
            $table->dropForeign(['prescribed_medicine_id']);
            $table->dropColumn('prescribed_medicine_id');
        });

        Schema::table('concurrent_therapy', function (Blueprint $table) {
            $table->dropForeign(['prescribed_medicine_id']);
            $table->dropColumn('prescribed_medicine_id');
        });
    }
}
