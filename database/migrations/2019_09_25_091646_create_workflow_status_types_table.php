<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkflowStatusTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflow_status_types', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');
            $table->integer('jira_id');

            // Relations
            $table->nullableMorphs('scope');

            // Attributes
            $table->string('name', 50)->index();
            $table->text('description')->nullable();
            $table->string('color', 20);

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
        Schema::dropIfExists('workflow_status_types');
    }
}
