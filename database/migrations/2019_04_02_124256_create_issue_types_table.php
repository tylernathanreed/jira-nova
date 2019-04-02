<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIssueTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('issue_types', function (Blueprint $table) {

            $table->bigIncrements('id');

            $table->integer('jira_id')->index();
            $table->string('display_name', 50);
            $table->string('description', 255)->nullable();
            $table->string('icon_url', 255);
            $table->boolean('is_subtask');

            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_types');
    }
}
