<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSlideshowPagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('slideshow_pages', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            // Relations
            $table->bigBelongsTo('slideshows', 'slideshow_id')->index();

            // Attributes
            $table->string('display_name', 50);
            $table->string('uri', 255);
            $table->integer('screentime')->unsigned();
            $table->integer('order')->unsigned();

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
        Schema::dropIfExists('slideshow_pages');
    }
}
