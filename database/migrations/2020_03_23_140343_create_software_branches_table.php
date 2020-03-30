<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSoftwareBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('software_branches', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            // Relations
            $table->bigBelongsTo('projects', 'project_id')->index();
            $table->bigBelongsTo('software_applications', 'application_id')->index();
            $table->bigBelongsTo('software_branch_tiers', 'branch_tier_id')->index();
            $table->bigBelongsTo('versions', 'target_version_id')->index()->nullable();

            // Attributes
            $table->string('name', 100);

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
        Schema::dropIfExists('software_branches');
    }
}
