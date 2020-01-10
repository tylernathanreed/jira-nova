<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJiraProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('jira')->create('projects', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');
            $table->string('cache_key', 100)->unique();
            $table->string('cache_value', 36)->index();

            // Attributes
            $table->integer('jira_id')->index();
            $table->string('jira_key', 10)->index();
            $table->string('entity_id', 36)->nullable()->index();
            $table->string('uuid', 36)->nullable()->index();
            $table->string('style', 20);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->text('avatar_urls');
            $table->bigBelongsTo('users', 'lead_id');
            $table->string('project_keys', 255);
            $table->string('project_type_key', 20);
            $table->boolean('is_simplified');
            $table->boolean('is_private');
            $table->text('properties')->nullable();

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
        Schema::connection('jira')->dropIfExists('projects');
    }
}
