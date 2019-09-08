<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIssueChangelogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('issue_changelogs', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');
            $table->bigInteger('jira_id')->unsigned()->unique();

            // Relations
            $table->bigBelongsTo('issues', 'issue_id')->index();
            $table->string('issue_key', 10)->index();

            $table->bigBelongsTo('users', 'author_id')->nullable()->index();
            $table->string('author_key', 35)->nullable()->index();
            $table->string('author_name', 35)->nullable();
            $table->string('author_icon_url', 255)->nullable();

            // Revision tracking
            $table->datetime('created_at');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_changelogs');
    }
}
