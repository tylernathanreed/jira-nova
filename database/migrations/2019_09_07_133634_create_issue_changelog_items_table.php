<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIssueChangelogItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('issue_changelog_items', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            // Relations
            $table->bigBelongsTo('issue_changelogs', 'issue_changelog_id')->index();
            $table->integer('item_index')->unsigned();

            // Constraints
            $table->unique(['issue_changelog_id', 'item_index']);

            // Attributes
            $table->string('item_field_name', 50)->index();
            $table->text('item_from')->nullable();
            $table->text('item_to')->nullable();

            // Indexes
            $table->index(['issue_changelog_id', 'item_field_name', 'item_from']);
            $table->index(['issue_changelog_id', 'item_field_name', 'item_to']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_changelog_items');
    }
}
