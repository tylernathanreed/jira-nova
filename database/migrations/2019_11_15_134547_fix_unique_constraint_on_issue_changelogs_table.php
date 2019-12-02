<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class FixUniqueConstraintOnIssueChangelogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('issue_changelogs', function (Blueprint $table) {

            $table->dropUnique('UX_issue_changelogs_jira_id');
            $table->unique(['jira_id', 'issue_id'], 'UX_issue_changelogs_jira_id_issue_id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('issue_changelogs', function (Blueprint $table) {

            $table->dropUnique('UX_issue_changelogs_jira_id_issue_id');
            $table->unique('jira_id');

        });
    }
}
