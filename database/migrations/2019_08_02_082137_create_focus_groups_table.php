<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFocusGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('focus_groups', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            // Attributes
            $table->string('display_name', 20);
            $table->string('system_name', 20)->nullable()->index();
            $table->string('description', 255)->nullable();
            $table->text('color');
            $table->integer('priority')->unsigned();
            $table->boolean('blocks_other_focuses');
            $table->text('criteria');

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
        Schema::dropIfExists('focus_groups');
    }
}
