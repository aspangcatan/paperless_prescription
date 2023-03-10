<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrescriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('hospital_id');
            $table->integer('physician_id');
            $table->integer('pgi_id')->nullable();
            $table->string('attending_physician');
            $table->string('station', 255);
            $table->string('ward', 255);
            $table->string('bed_no', 255);
            $table->string('urgency', 20);
            $table->integer('type');
            $table->string('drug_allergy', 255)->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->string('serum_creatinine', 50)->nullable();
            $table->string('creatinine_clearance', 50)->nullable();
            $table->string('transaminases', 50)->nullable();
            $table->string('sgpt', 50)->nullable();
            $table->string('sgot', 50)->nullable();
            $table->integer('days_requested')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prescriptions');
    }
}
