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

            $table->string('reporter_key', 64)->nullable()->change();
            $table->string('assignee_key', 64)->nullable()->change();

        });

        Schema::table('users', function(Blueprint $table) {

            $table->string('jira_key', 64)->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
