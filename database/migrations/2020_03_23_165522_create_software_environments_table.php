<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSoftwareEnvironmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('software_environments');

        Schema::create('software_environments', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            // Relations
            $table->bigBelongsTo('software_branches', 'branch_id')->index();
            $table->bigBelongsTo('software_brands', 'brand_id')->index();
            $table->bigBelongsTo('software_environment_tiers', 'environment_tier_id', 'id', 'FK_software_environments_environment_tiers_tier_id')->index();

            // Attributes
            $table->string('url', 255);
            $table->string('name', 100);
            $table->text('description')->nullable();

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
        Schema::dropIfExists('software_environments');
    }
}
