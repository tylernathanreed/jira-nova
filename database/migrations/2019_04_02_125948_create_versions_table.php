<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('versions', function (Blueprint $table) {

            $table->bigIncrements('id');

            $table->belongsTo('projects', 'project_id')->index();
            $table->integer('jira_id')->index();

            $table->string('display_name', 50);
            $table->date('start_date')->nullable();
            $table->date('release_date')->nullable();
            $table->boolean('archived');
            $table->boolean('released');
            $table->boolean('overdue');

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
        Schema::dropIfExists('versions');
    }
}
