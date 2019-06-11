<?php

use Carbon\Carbon;
use App\Models\Schedule;
use Faker\Generator as Faker;
use App\Models\ScheduleAssociation;
use App\Models\ScheduleWeekTemplate;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(ScheduleAssociation::class, function (Faker $faker) {

	$schedule = Schedule::skip($faker->numberBetween(0, Schedule::count() - 1))->first();
	$template = ScheduleWeekTemplate::skip($faker->numberBetween(0, ScheduleWeekTemplate::count() - 1))->first();

	$start = Carbon::now()->addDays($faker->numberBetween(-30, 365));
	$end = $start->copy()->addDays($faker->numberBetween(0, 365));

	$hierarchy = $faker->numberBetween(1, 10000);

    return [
        'schedule_id' => $schedule->id,
        'schedule_system_name' => $schedule->system_name,
        'schedule_week_template_id' => $template->id,
        'schedule_week_template_system_name' => $template->system_name,
        'start_date' => $start,
        'end_date' => $end,
        'hierarchy' => $hierarchy
    ];

});
