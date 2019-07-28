<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIssuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('issues', function (Blueprint $table) {

            $table->bigIncrements('id');

            $table->string('key', 10)->unique();
            $table->string('type_name', 50)->nullable()->index();
            $table->string('type_icon_url', 255)->nullable();
            $table->boolean('is_subtask');
            $table->string('parent_key', 10)->nullable()->index();
            $table->string('status_name', 50)->nullable()->index();
            $table->string('status_color', 50)->nullable();
            $table->text('summary');
            $table->date('due_date')->nullable();
            $table->integer('estimate_remaining')->unsigned()->nullable();
            $table->date('estimate_date')->nullable();
            $table->integer('estimate_diff')->nullable();
            $table->string('priority_name', 20)->index();
            $table->string('priority_icon_url', 255)->nullable();
            $table->string('reporter_name', 35)->nullable()->index();
            $table->string('reporter_icon_url', 255)->nullable();
            $table->string('assignee_name', 35)->nullable()->index();
            $table->string('assignee_icon_url', 255)->nullable();
            $table->string('issue_category', 50)->nullable();
            $table->string('focus', 20)->nullable()->index();
            $table->string('epic_key', 10)->nullable()->index();
            $table->string('epic_url', 255)->nullable();
            $table->string('epic_name', 50)->nullable();
            $table->string('epic_color', 20)->nullable();
            $table->text('labels')->nullable();
            $table->text('links')->nullable();
            $table->text('blocks')->nullable();
            $table->string('rank', 50)->index();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issues');
    }
}
