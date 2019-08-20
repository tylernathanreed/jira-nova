<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIssuesLabelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('issues_labels', function (Blueprint $table) {

            $table->bigBelongsTo('issues', 'issue_id')->index();
            $table->string('label_name', 50)->index();
            $table->foreign('label_name')->references('name')->on('labels')->onDelete('cascade')->onUpdate('cascade');

            $table->index(['issue_id', 'label_name']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issues_labels');
    }
}
