<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {

            // Identification
            $table->bigIncrements('id');

            // Attributes
            $table->string('jira_id', 32)->nullable()->index();
            $table->string('display_name', 100);
            $table->string('email_address')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 80)->nullable();
            $table->string('api_token', 80)->unique()->nullable();
            $table->rememberToken();

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
        Schema::dropIfExists('users');
    }
}
