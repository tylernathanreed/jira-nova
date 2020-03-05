<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJiraIssueTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('jira')->dropIfExists('issue_types');

        Schema::connection('jira')->create('issue_types', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');
            $table->string('cache_key', 100)->unique();
            $table->string('cache_value', 36)->index();

            // Attributes
            $table->integer('jira_id')->index();
            $table->string('entity_id')->index()->nullable();
            $table->string('name', 255)->index();
            $table->text('description')->nullable();
            $table->boolean('subtask');
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
        Schema::connection('jira')->dropIfExists('issue_types');
    }
}
