<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIssueIdAuthorKeyIndexOnIssueChangelogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('issue_changelogs', function (Blueprint $table) {
            $table->index(['issue_id', 'author_key']);
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
            $table->dropIndex('IX_issue_changelogs_issue_id_author_key');
        });
    }
}
