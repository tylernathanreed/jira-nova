<?php

use Faker\Generator as Faker;
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

$factory->define(ScheduleWeekTemplate::class, function (Faker $faker) {

	$name = $faker->name;
	$type = (['daily', 'weekly'])[$faker->numberBetween(0, 1)];

	$total = 16;
	$allocations = [];
	$focus = ['dev', 'ticket', 'other'];

	if($type == 'weekly') {

		$weights = array_reduce($focus, function($weights, $focus) use ($faker) {
			return $weights + [$focus => $faker->randomDigit];
		}, []);

		$sum = array_sum($weights);

		foreach($focus as $f) {
			$allocations[$f] = $weights[$f] == 0 ? 0 : round($weights[$f] / $sum * $total) * 1800 * 5;
		}

	} else {

		$days = [0, 1, 2, 3, 4, 5, 6];

		foreach($days as $day) {

			$weights = array_reduce($focus, function($weights, $focus) use ($faker) {
				return $weights + [$focus => $faker->randomDigit];
			}, []);

			$sum = array_sum($weights);

			foreach($focus as $f) {
				$allocations[$day][$f] = $weights[$f] == 0 ? 0 : round($weights[$f] / $sum * $total) * 1800;
			}

		}

	}

    return [
        'display_name' => $name,
        'system_name' => null,
        'description' => null,
        'due_date_in_week' => $faker->numberBetween(0, 6),
        'allocation_type' => $type,
        'allocations' => json_encode($allocations)
    ];

});
