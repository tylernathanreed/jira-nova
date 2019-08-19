<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEpicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('epics', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            // Relations
            $table->bigBelongsTo('projects', 'project_id')->index()->nullable();
            $table->string('project_key', 4)->nullable();

            // Attributes
            $table->string('key', 10)->nullable()->unique();
            $table->string('url', 255)->nullable();
            $table->string('name', 50)->nullable()->index();
            $table->string('color', 20)->nullable();

            $table->text('summary')->nullable();
            $table->text('description')->nullable();

            $table->boolean('active');

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
        Schema::dropIfExists('epics');
    }
}
