<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkflowStatusGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflow_status_groups', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            $table->string('display_name', 50);
            $table->string('system_name', 50)->index()->nullable();
            $table->integer('transition_order')->unsigned();
            $table->string('color', 20);
            $table->string('description', 255)->nullable();

            // Revisions
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
        Schema::dropIfExists('workflow_status_groups');
    }
}
