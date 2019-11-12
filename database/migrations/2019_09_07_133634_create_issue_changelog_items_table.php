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
        Schema::dropIfExists('issue_changelog_items');

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
            if(Schema::getConnection()->getDriverName() != 'mysql') {

                $table->index(['issue_changelog_id', 'item_field_name', 'item_from'], 'IX_issue_changelog_items_specific_from');
                $table->index(['issue_changelog_id', 'item_field_name', 'item_to'], 'IX_issue_changelog_items_specific_to');

            }

        });

        if(Schema::getConnection()->getDriverName() == 'mysql') {

            DB::statement('alter table `issue_changelog_items` add index `IX_issue_changelog_items_specific_from` (`issue_changelog_id`, `item_field_name`, `item_from`(50))');
            DB::statement('alter table `issue_changelog_items` add index `IX_issue_changelog_items_specific_to` (`issue_changelog_id`, `item_field_name`, `item_to`(50))');

        }

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
