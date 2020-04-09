<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJiraVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('jira')->dropIfExists('versions');

        Schema::connection('jira')->create('versions', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');
            $table->string('cache_key', 100)->unique();
            $table->string('cache_value', 36)->index();

            // Attributes
            $table->integer('jira_id')->index();
            $table->bigBelongsTo('projects', 'project_id', 'id', 'FK_jira_versions_projects_project_id')->index();
            $table->string('name', 255)->index();
            $table->text('description')->nullable();
            $table->boolean('archived');
            $table->boolean('released');
            $table->date('start_date')->nullable();
            $table->date('release_date')->nullable();
            $table->boolean('overdue')->nullable();

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
        Schema::connection('jira')->dropIfExists('versions');
    }
}
