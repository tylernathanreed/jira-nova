<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(FocusGroupsTableSeeder::class);
        $this->call(SchedulesTableSeeder::class);
        $this->call(ScheduleFocusAllocationsTableSeeder::class);
        $this->call(CachesTableSeeder::class);
    }
}
