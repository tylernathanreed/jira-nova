<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIssueWeekDatesView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('drop view if exists vw_issue_week_dates');

        DB::statement("
            create view vw_issue_week_dates as
                select
                    issues_labels.issue_id,
                    min(date_add('2019-07-12', interval cast(substring(labels.name, 5, 4) as unsigned integer) * 7 day)) as week_date
                from labels
                    inner join issues_labels
                        on issues_labels.label_name = labels.name
                where labels.name like 'Week%'
                group by issues_labels.issue_id
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('drop view if exists vw_issue_week_dates');
    }
}
