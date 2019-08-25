<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSimpleTypeColumnsToSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('schedules', function (Blueprint $table) {

            $table->string('type', 20)->nullable();
            $table->integer('simple_weekly_allocation')->unsigned()->nullable();

        });

        DB::table('schedules')->update([
            'type' => 'Advanced'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('simple_weekly_allocation');
        });
    }
}
