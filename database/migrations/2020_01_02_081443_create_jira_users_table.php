<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJiraUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('jira')->create('users', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');
            $table->string('cache_key', 100)->unique();
            $table->string('cache_value', 36);

            // Attributes
            $table->string('account_id', 255)->index();
            $table->string('account_type', 20);
            $table->text('avatar_urls');
            $table->string('display_name', 255)->index();
            $table->boolean('is_active');
            $table->string('timezone', 50)->nullable();
            $table->string('locale', 20)->nullable();

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
        Schema::connection('jira')->dropIfExists('users');
    }
}
