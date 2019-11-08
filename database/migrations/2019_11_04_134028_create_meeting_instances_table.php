<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeetingInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meeting_instances', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            // Attributes
            $table->string('name', 50);
            $table->date('effective_date')->index();
            $table->datetime('starts_at');
            $table->datetime('ends_at');
            $table->integer('length_in_seconds')->unsigned();

            // Revision tracking
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
        Schema::dropIfExists('meeting_instances');
    }
}
