<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndicationUseDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('indication_use_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('indication_id');
            $table->integer('row_id');
            $table->string('organism');
            $table->string('details')->nullable();
            $table->string('pattern')->nullable();
            $table->timestamps();

            $table->foreign('indication_id')->references('id')->on('indication_use')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('indication_use_details');
    }
}
