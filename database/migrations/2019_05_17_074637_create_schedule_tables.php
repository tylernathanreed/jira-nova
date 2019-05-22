<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScheduleTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schedule_week_templates', function (Blueprint $table) {

            // Identification
            $table->increments('id');

            // Attributes
            $table->string('display_name', 50);
            $table->string('system_name', 50)->nullable()->index()->unique();
            $table->string('description', 255)->nullable();
            $table->tinyInteger('due_date_in_week')->unsigned();
            $table->string('allocation_type', 20)->index();

            // Revision tracking
            $table->timestamps();
            $table->softDeletes();

        });

        Schema::create('schedules', function (Blueprint $table) {

            // Identification
            $table->increments('id');

            // Attributes
            $table->string('display_name', 50);
            $table->string('system_name', 50)->nullable()->index()->unique();
            $table->string('description', 255)->nullable();

            // Relations
            $table->belongsTo('schedule_week_templates', 'week_template_id')->index();
            $table->string('week_template_system_name', 50)->nullable()->index();

            // Revision tracking
            $table->timestamps();
            $table->softDeletes();

        });

        Schema::create('schedule_day_templates', function (Blueprint $table) {

            // Identification
            $table->increments('id');

            // Relations
            $table->belongsTo('schedule_week_templates', 'week_template_id')->index();
            $table->string('week_template_system_name', 50)->nullable()->index();

            // Attributes
            $table->tinyInteger('day_in_week')->unsigned();

            // Constraints
            $table->unique(['week_template_id', 'day_in_week']);

            // Revision tracking
            $table->timestamps();

        });

        Schema::create('schedule_weeks', function (Blueprint $table) {

            // Identification
            $table->increments('id');

            // Relations
            $table->belongsTo('schedules', 'schedule_id')->index();
            $table->string('schedule_system_name', 50)->nullable()->index();
            $table->integer('week_number')->unsigned();

            $table->belongsTo('schedule_week_templates', 'week_template_id')->index();
            $table->string('week_template_system_name', 50)->nullable()->index();

            // Attributes
            $table->date('start_date');
            $table->date('due_date');
            $table->string('allocation_type', 20);

            // Constraints
            $table->unique(['schedule_id', 'week_number']);

            // Revision tracking
            $table->timestamps();

        });

        Schema::create('schedule_days', function (Blueprint $table) {

            // Identification
            $table->increments('id');

            // Relations
            $table->belongsTo('schedules', 'schedule_id')->index();
            $table->string('schedule_system_name', 50)->nullable()->index();

            $table->belongsTo('schedule_weeks', 'schedule_week_id')->index();
            $table->integer('week_number')->unsigned();
            $table->tinyInteger('day_in_week')->unsigned();

            $table->belongsTo('schedule_day_templates', 'day_template_id')->index();
            $table->string('day_template_system_name', 50)->nullable()->index();

            // Attributes
            $table->date('date');

            // Constraints
            $table->unique(['schedule_id', 'week_number', 'day_in_week']);

            // Revision tracking
            $table->timestamps();

        });

        Schema::create('schedule_allocations', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            // Relations
            $table->morphsTo('reference')->index();
            $table->string('reference_system_name')->nullable()->index();

            // Attributes
            $table->string('focus_type', 20);
            $table->integer('focus_allocation')->unsigned();

            // Constraints
            $table->unique(['reference_id', 'reference_type', 'focus_type']);

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
        Schema::dropIfExists('schedule_allocations');
        Schema::dropIfExists('schedule_days');
        Schema::dropIfExists('schedule_weeks');
        Schema::dropIfExists('schedule_day_templates');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('schedule_week_templates');
    }
}
