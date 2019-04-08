<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIssueFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('issue_fields', function (Blueprint $table) {

            $table->bigIncrements('id');

            $table->bigBelongsTo('projects', 'project_id')->index();

            $table->integer('jira_id')->unsigned()->nullable()->index();
            $table->string('jira_key', 50)->index();

            $table->string('display_name', 50);
            $table->string('schema_type', 50);
            $table->string('schema_items', 100)->nullable();
            $table->string('schema_system', 50)->nullable();
            $table->string('schema_custom', 255)->nullable();
            $table->string('operations')->nullable();
            $table->string('auto_complete_url', 255)->nullable();
            $table->text('allowed_values')->nullable();
            $table->boolean('has_default_value');
            $table->string('default_value')->nullable();
            $table->boolean('required');

            $table->timestamps();
            $table->softDeletes();

        });

        Schema::create('issue_type_fields', function (Blueprint $table) {

            $table->bigBelongsTo('issue_types', 'issue_type_id');
            $table->bigBelongsTo('issue_fields', 'issue_field_id');

            $table->index(['issue_types', 'issue_fields']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_fields');
        Schema::dropIfExists('issue_type_fields');
    }
}
