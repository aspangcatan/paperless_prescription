<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNursePrescriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        #REASON WHY WE SEPARATE THIS TABLE IS WHEN DATA BECOMES LARGE
        #THERE WILL BE A NOTICABLE DECLINE OF PERFORMANCE WHEN QUERY
        Schema::create('nurse_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescription_id')->unique();
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
        Schema::dropIfExists('nurse_prescriptions');
    }
}
