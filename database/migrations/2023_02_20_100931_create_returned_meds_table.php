<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnedMedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('returned_meds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescribed_meds_id');
            $table->integer('returned_by');
            $table->integer('returned_amount');
            $table->text('remarks');
            $table->integer('received_by')->nullable();
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
        Schema::dropIfExists('returned_meds');
    }
}
