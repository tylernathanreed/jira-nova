<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCachesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('caches', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            // Attributes
            $table->string('model_class', 255)->index();
            $table->string('cache_interval', 100)->nullable();
            $table->string('status', 20)->nullable();
            $table->datetime('build_started_at')->nullable();
            $table->datetime('build_completed_at')->nullable();
            $table->integer('build_record_count')->unsigned()->nullable();
            $table->integer('build_record_total')->unsigned()->nullable();
            $table->datetime('update_started_at')->nullable();
            $table->datetime('update_completed_at')->nullable();
            $table->integer('update_record_count')->unsigned()->nullable();
            $table->integer('update_record_total')->unsigned()->nullable();
            $table->integer('updates_since_build')->unsigned()->nullable();

            // Revision tracking
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
        Schema::dropIfExists('caches');
    }
}
