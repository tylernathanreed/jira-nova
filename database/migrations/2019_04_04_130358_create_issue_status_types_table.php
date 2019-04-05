<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIssueStatusTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('issue_status_categories', function (Blueprint $table) {

            $table->bigIncrements('id');

            $table->bigBelongsTo('projects', 'project_id')->index();

            $table->integer('jira_id')->unsigned()->index();
            $table->string('jira_key', 20)->index();
            $table->string('display_name', 20);
            $table->string('color_name', 20);

            $table->timestamps();
            $table->softDeletes();

        });

        Schema::create('issue_status_types', function (Blueprint $table) {

            $table->bigIncrements('id');

            $table->bigBelongsTo('projects', 'project_id')->index();
            $table->bigBelongsTo('issue_status_categories', 'issue_status_category_id')->index();

            $table->integer('jira_id')->unsigned()->index();
            $table->string('display_name', 20);
            $table->string('icon_url', 255)->nullable();
            $table->string('description', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

        });

        Schema::create('issue_type_status_types', function (Blueprint $table) {

            $table->bigBelongsTo('issue_types', 'issue_type_id');
            $table->bigBelongsTo('issue_status_types', 'issue_status_type_id');

            $table->index(['issue_type_id', 'issue_status_type_id']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_status_categories');
        Schema::dropIfExists('issue_status_types');
        Schema::dropIfExists('issue_type_status_types');
    }
}
