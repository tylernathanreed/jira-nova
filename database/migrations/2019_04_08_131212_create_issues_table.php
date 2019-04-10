<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIssuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('issues', function (Blueprint $table) {

            $table->bigIncrements('id');

            $table->integer('jira_id')->unsigned()->index();
            $table->string('jira_key', 20)->index();
            $table->bigBelongsTo('projects', 'project_id')->index();
            $table->bigBelongsToColumn('parent_issue_id')->nullable()->index();
            $table->bigBelongsTo('issue_types', 'issue_type_id')->index();
            $table->bigBelongsTo('issue_categories', 'issue_category_id')->nullable()->index();
            $table->bigBelongsTo('priorities', 'priority_id')->index();
            $table->bigBelongsTo('issue_status_types', 'status_id')->index();
            $table->bigBelongsTo('users', 'reporter_id')->index();
            $table->bigBelongsTo('users', 'assignee_id')->nullable()->index();
            $table->string('summary', 255);
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('estimated_seconds')->unsigned()->nullable();
            $table->integer('remaining_seconds')->unsigned()->nullable();

            $table->datetime('last_viewed_at')->nullable();
            $table->bigBelongsTo('users', 'created_by_id')->index();
            $table->timestamps();
            $table->softDeletes();

        });

        Schema::table('issues', function (Blueprint $table) {
            $table->belongsToForeign('issues', 'parent_issue_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issues');
    }
}
