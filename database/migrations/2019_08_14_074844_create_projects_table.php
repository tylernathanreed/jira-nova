<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            // Attributes
            $table->integer('jira_id')->unsigned();
            $table->string('jira_key', 4)->index();
            $table->string('name', 20);
            $table->string('avatar_url', 255);

            // Revision tracking
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
        Schema::dropIfExists('projects');
    }
}
