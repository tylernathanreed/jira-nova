<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendProjectKeyLengthOnProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function(Blueprint $table) {
            $table->string('jira_key', 6)->change();
        });

        Schema::table('issues', function(Blueprint $table) {
            $table->string('project_key', 6)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function(Blueprint $table) {
            $table->string('jira_key', 4)->change();
        });

        Schema::table('issues', function(Blueprint $table) {
            $table->string('project_key', 4)->nullable()->change();
        });
    }
}
