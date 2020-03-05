<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJiraWorkflowStatusTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('jira')->dropIfExists('workflow_status_types');

        Schema::connection('jira')->create('workflow_status_types', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');
            $table->string('cache_key', 100)->unique();
            $table->string('cache_value', 36)->index();

            // Attributes
            $table->integer('jira_id')->unsigned()->index();
            $table->bigBelongsTo('workflow_status_categories', 'workflow_status_category_id', 'id', 'FK_workflow_status_types_category_id');
            $table->string('name', 60);
            $table->text('description')->nullable();
            $table->string('icon_url');

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
        Schema::connection('jira')->dropIfExists('workflow_status_types');
    }
}
