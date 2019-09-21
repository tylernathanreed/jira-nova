<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimeOffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_off', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            // Relations
            $table->bigBelongsTo('users', 'user_id')->index();

            // Attributes
            $table->date('date');
            $table->percent('percent')->unsigned();
            $table->string('description', 255)->nullable();

            // Revision tracking
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_off');
    }
}
