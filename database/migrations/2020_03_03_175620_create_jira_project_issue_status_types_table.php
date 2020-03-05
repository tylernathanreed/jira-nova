<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJiraProjectIssueStatusTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('jira')->dropIfExists('project_issue_status_types');

        Schema::connection('jira')->create('project_issue_status_types', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');
            $table->string('cache_key', 100)->unique();
            $table->string('cache_value', 36)->index();

            // Attributes
            $table->bigBelongsTo('projects', 'project_id');
            $table->bigBelongsTo('issue_types', 'issue_type_id');
            $table->bigBelongsTo('workflow_status_types', 'workflow_status_type_id', 'id', 'FK_project_issue_status_types_status_type_id');

            // Composite indexes
            $table->index(['project_id', 'issue_type_id', 'workflow_status_type_id'], 'IX_project_issue_status_types_foreign_keys');

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
        Schema::connection('jira')->dropIfExists('project_issue_status_types');
    }
}
