<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScheduleFocusAllocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schedule_focus_allocations', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            // Relations
            $table->bigBelongsTo('schedules', 'schedule_id')->index();
            $table->bigBelongsTo('focus_groups', 'focus_group_id')->index();

            // Attributes
            $table->integer('sunday_allocation')->unsigned();
            $table->integer('monday_allocation')->unsigned();
            $table->integer('tuesday_allocation')->unsigned();
            $table->integer('wednesday_allocation')->unsigned();
            $table->integer('thursday_allocation')->unsigned();
            $table->integer('friday_allocation')->unsigned();
            $table->integer('saturday_allocation')->unsigned();

            // Composite indexes
            $table->index(['schedule_id', 'focus_group_id']);

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
        Schema::dropIfExists('schedule_focus_allocations');
    }
}
