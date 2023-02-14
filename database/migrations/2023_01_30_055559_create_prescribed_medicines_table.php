<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrescribedMedicinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prescribed_medicines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("prescription_id");
            $table->string("description");
            $table->string("unit");
            $table->string("dosage_strength", 100);
            $table->string("dosage_unit", 100);
            $table->string("frequency", 100);
            $table->integer("availability")->nullable();

//            $table->integer("quantity_ordered");
//            $table->integer("quantity_rendered")->nullable();
//            $table->integer("quantity_returned")->nullable();

            $table->foreign('prescription_id')->references('id')->on('prescriptions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prescribed_medicines');
    }
}
