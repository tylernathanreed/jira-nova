<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIssueWorklogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('issue_worklogs', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');
            $table->bigInteger('jira_id')->unsigned()->unique();

            // Relations
            $table->bigBelongsTo('issues', 'issue_id')->index();
            $table->bigBelongsTo('users', 'author_id')->nullable()->index();
            $table->string('author_key', 35)->nullable()->index();
            $table->string('author_name', 35)->nullable();
            $table->string('author_icon_url', 255)->nullable();

            // Attributes
            $table->integer('time_spent')->unsigned();
            $table->datetime('started_at');

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
        Schema::dropIfExists('issue_worklogs');
    }
}
