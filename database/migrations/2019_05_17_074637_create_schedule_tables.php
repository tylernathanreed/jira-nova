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
        Schema::create('schedules', function (Blueprint $table) {

            // Identification
            $table->increments('id');

            // Attributes
            $table->string('display_name', 50);
            $table->string('system_name', 50)->nullable()->index()->unique();
            $table->string('description', 255)->nullable();

            // Revision tracking
            $table->timestamps();
            $table->softDeletes();

        });

        Schema::create('schedule_week_templates', function (Blueprint $table) {

            // Identification
            $table->increments('id');

            // Attributes
            $table->string('display_name', 50);
            $table->string('system_name', 50)->nullable()->index()->unique();
            $table->string('description', 255)->nullable();
            $table->tinyInteger('due_date_in_week')->unsigned();
            $table->string('allocation_type', 20)->index();
            $table->text('allocations');

            // Revision tracking
            $table->timestamps();
            $table->softDeletes();

        });

        Schema::create('schedule_associations', function (Blueprint $table) {

            // Identification
            $table->increments('id');

            // Relations
            $table->belongsTo('schedules', 'schedule_id');
            $table->string('schedule_system_name', 50)->nullable();
            $table->belongsTo('schedule_week_templates', 'schedule_week_template_id');
            $table->string('schedule_week_template_system_name', 50)->nullable();

            // Attributes
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('hierarchy')->unsigned();

            // Composite indexes
            $table->index(['schedule_id', 'schedule_week_template_id']);
            $table->index(['schedule_system_name', 'schedule_week_template_id']);

            // Unique constraints
            $table->unique(['schedule_id', 'hierarchy']);

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
        Schema::dropIfExists('schedule_associations');
        Schema::dropIfExists('schedule_week_templates');
        Schema::dropIfExists('schedules');
    }
}
