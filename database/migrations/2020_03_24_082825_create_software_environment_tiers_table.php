<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSoftwareEnvironmentTiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('software_environment_tiers', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            // Attributes
            $table->string('name', 100)->index();
            $table->smallInteger('pipeline_order')->unsigned();
            $table->text('description')->nullable();

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
        Schema::dropIfExists('software_environment_tiers');
    }
}
