<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendUserKeyColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('issues', function(Blueprint $table) {

            $table->string('reporter_key', 80)->nullable()->change();
            $table->string('assignee_key', 80)->nullable()->change();

        });

        Schema::table('users', function(Blueprint $table) {

            $table->string('jira_key', 80)->nullable()->change();

        });

        Schema::table('issue_changelogs', function(Blueprint $table) {

            $table->string('author_key', 80)->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('issues', function(Blueprint $table) {

            $table->string('reporter_key', 35)->nullable()->change();
            $table->string('assignee_key', 35)->nullable()->change();

        });

        Schema::table('users', function(Blueprint $table) {

            $table->string('jira_key', 35)->nullable()->change();

        });

        Schema::table('issue_changelogs', function(Blueprint $table) {

            $table->string('author_key', 35)->nullable()->change();

        });
    }
}
