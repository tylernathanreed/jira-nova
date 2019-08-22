<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIssuesFixVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('issues_fix_versions', function (Blueprint $table) {

            $table->bigBelongsTo('issues', 'issue_id')->index();
            $table->bigBelongsTo('versions', 'version_id')->index();

            $table->index(['issue_id', 'version_id']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issues_fix_versions');
    }
}
