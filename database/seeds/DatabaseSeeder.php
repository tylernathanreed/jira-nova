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
        $this->call(SlideshowsTableSeeder::class);
        $this->call(SlideshowPagesTableSeeder::class);
        $this->call(WorkflowStatusGroupsTableSeeder::class);
        $this->call(WorkflowStatusTypesTableSeeder::class);
    }
}
