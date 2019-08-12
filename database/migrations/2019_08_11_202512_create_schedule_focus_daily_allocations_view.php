<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScheduleFocusDailyAllocationsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('drop view if exists vw_schedule_focus_daily_allocations');

        DB::statement("
            create view vw_schedule_focus_daily_allocations as
                select
                    schedule_id,
                    focus_group_id,
                    days_of_week.day_order,
                    days_of_week.day_of_week,
                    case
                        when days_of_week.day_of_week = 'Sunday'
                            then schedule_focus_allocations.sunday_allocation
                        when days_of_week.day_of_week = 'Monday'
                            then schedule_focus_allocations.monday_allocation
                        when days_of_week.day_of_week = 'Tuesday'
                            then schedule_focus_allocations.tuesday_allocation
                        when days_of_week.day_of_week = 'Wednesday'
                            then schedule_focus_allocations.wednesday_allocation
                        when days_of_week.day_of_week = 'Thursday'
                            then schedule_focus_allocations.thursday_allocation
                        when days_of_week.day_of_week = 'Friday'
                            then schedule_focus_allocations.friday_allocation
                        when days_of_week.day_of_week = 'Saturday'
                            then schedule_focus_allocations.saturday_allocation
                        else 0
                    end as allocation
                from schedule_focus_allocations
                    inner join (
                        select 'Sunday' as day_of_week, 0 as day_order union all
                        select 'Monday' as day_of_week, 1 as day_order union all
                        select 'Tuesday' as day_of_week, 2 as day_order union all
                        select 'Wednesday' as day_of_week, 3 as day_order union all
                        select 'Thursday' as day_of_week, 4 as day_order union all
                        select 'Friday' as day_of_week, 5 as day_order union all
                        select 'Saturday' as day_of_week, 6 as day_order
                    ) as days_of_week
                        on 1 = 1
                where schedule_focus_allocations.deleted_at is null
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('drop view if exists vw_schedule_focus_daily_allocations');
    }
}
