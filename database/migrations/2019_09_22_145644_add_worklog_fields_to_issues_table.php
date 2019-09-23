<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWorklogFieldsToIssuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('issues', function (Blueprint $table) {

            $table->integer('worklogs_count')->unsigned()->nullable();
            $table->datetime('worklogs_updated_at')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('issues', function (Blueprint $table) {

            $table->dropColumn('worklogs_count');
            $table->dropColumn('worklogs_updated_at');

        });
    }
}
