<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrescriptionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prescription_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("prescription_id");
            $table->string("origin");
            $table->integer("released_by");
            $table->string("released_to");
            $table->dateTime("date_forwarded")->useCurrent();
            $table->string("remarks")->nullable();
            $table->integer("status")->default(0); //USE TO DETERMINE IF PRESCRIPTION CYCLE IS DONE
            $table->timestamps();

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
        Schema::dropIfExists('prescription_logs');
    }
}
