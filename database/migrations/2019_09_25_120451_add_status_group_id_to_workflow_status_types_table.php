<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusGroupIdToWorkflowStatusTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workflow_status_types', function (Blueprint $table) {
            $table->bigBelongsTo('workflow_status_groups', 'status_group_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workflow_status_types', function (Blueprint $table) {

            $table->dropIndex('IX_workflow_status_types_status_group_id');
            $table->dropBelongsTo('workflow_status_groups', 'status_group_id');

        });
    }
}
