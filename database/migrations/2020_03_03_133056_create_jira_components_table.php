<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJiraComponentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('jira')->dropIfExists('components');

        Schema::connection('jira')->create('components', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');
            $table->string('cache_key', 100)->unique();
            $table->string('cache_value', 36)->index();

            // Attributes
            $table->integer('jira_id')->index();
            $table->bigBelongsTo('projects', 'project_id')->index();
            $table->string('name', 255)->index();
            $table->text('description')->nullable();
            $table->bigBelongsTo('users', 'lead_id')->nullable()->index();
            $table->string('assignee_type', 20);
            $table->bigBelongsTo('users', 'assignee_id')->nullable()->index();
            $table->string('real_assignee_type', 20);
            $table->bigBelongsTo('users', 'real_assignee_id')->nullable()->index();
            $table->boolean('is_assignee_type_valid');

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
        Schema::connection('jira')->dropIfExists('components');
    }
}
