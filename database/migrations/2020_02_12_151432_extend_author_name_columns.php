<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendAuthorNameColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('issues', function(Blueprint $table) {

            $table->string('reporter_name', 80)->nullable()->change();
            $table->string('assignee_name', 80)->nullable()->change();

        });

        Schema::table('issue_changelogs', function(Blueprint $table) {

            $table->string('author_name', 80)->nullable()->change();

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

            $table->string('reporter_name', 35)->nullable()->change();
            $table->string('assignee_name', 35)->nullable()->change();

        });

        Schema::table('issue_changelogs', function(Blueprint $table) {

            $table->string('author_name', 35)->nullable()->change();

        });
    }
}
