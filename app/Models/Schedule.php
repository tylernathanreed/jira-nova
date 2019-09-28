<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    /////////////////
    //* Constants *//
    /////////////////
    /**
     * The schedule type constants.
     *
     * @var string
     */
    const TYPE_SIMPLE = 'Simple';
    const TYPE_ADVANCED = 'Advanced';

    /**
     * The pre-defined schedules.
     *
     * @var string
     */
    const SCHEDULE_DEFAULT = 'simple';

    //////////////
    //* Traits *//
    //////////////
    use SoftDeletes;

    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'schedules';

    /////////////////
    //* Accessors *//
    /////////////////
    /**
     * Returns the default schedule.
     *
     * @return static
     */
    public static function getDefaultSchedule()
    {
        return (new Schedule)->setAttribute('type', static::TYPE_SIMPLE)->setAttribute('simple_weekly_allocation', 30);
    }

    /**
     * Returns the weekly allocation total.
     *
     * @return integer
     */
    public function getWeeklyAllocationTotal()
    {
        // Check for simple
        if($this->type == static::TYPE_SIMPLE) {
            return $this->simple_weekly_allocation * 3600;
        }

        // Use the advanced calculation
        return (
            $this->allocations->sum('sunday_allocation') +
            $this->allocations->sum('monday_allocation') +
            $this->allocations->sum('tuesday_allocation') +
            $this->allocations->sum('wednesday_allocation') +
            $this->allocations->sum('thursday_allocation') +
            $this->allocations->sum('friday_allocation') +
            $this->allocations->sum('saturday_allocation')
        );
    }

    //////////////////
    //* Estimation *//
    //////////////////
    /**
     * Returns the allocation limit for the specified day of the week.
     *
     * @param  integer      $day
     * @param  string|null  $focus
     *
     * @return integer
     */
    public function getAllocationLimit($day, $focus = null)
    {
        // Determine the weekday allocations
        $allocations = $this->getWeekdayAllocations();

        // If we're using a simple schedule, return the limit for the day
        if($this->type == static::TYPE_SIMPLE) {
            return $allocations[$day];
        }

        // Return the focus limit for the day
        return $allocations[$day][$focus];
    }

    /**
     * Returns the first assignment date for each focus.
     *
     * @return array
     */
    public function getFirstAssignmentDatesByFocus()
    {
        // If this is a simple schedule, don't split by focus
        if($this->type == static::TYPE_SIMPLE) {
            return ['all' => $this->getFirstAssignmentDate()];
        }

        // Return the first assignment date for each focus
        return [
            Issue::FOCUS_DEV => $this->getFirstAssignmentDate(Issue::FOCUS_DEV),
            Issue::FOCUS_TICKET => $this->getFirstAssignmentDate(Issue::FOCUS_TICKET),
            Issue::FOCUS_OTHER => $this->getFirstAssignmentDate(Issue::FOCUS_OTHER)
        ];
    }

    /**
     * Returns the first assignment date for the specified focus.
     *
     * @param  string|null  $focus
     *
     * @return \Carbon\Carbon
     */
    public function getFirstAssignmentDate($focus = null)
    {
        // Determine the soonest we can start scheduling
        $start = carbon()->lte(carbon('11 AM')) // If it's prior to 11 AM
            ? carbon()->startOfDay() // Start no sooner than today
            : carbon()->addDays(1)->startOfDay(); // Otherwise, start no sooner than tomorrow

        // Determine the latest we can start scheduling
        $end = carbon()->addDays(8)->startOfDay(); // Start no later than a week after tomorrow

        // Determine the weekday allocations
        $allocations = $this->getWeekdayAllocations();

        // Determine the first date where we can start assigning due dates
        $date = array_reduce(array_keys($allocations), function($date, $key) use ($start, $focus, $allocations) {

            // If the schedule is simple, and has no allocation for the day, don't change the date
            if($this->type == static::TYPE_SIMPLE && $allocations[$key] <= 0) {
                return $date;
            }

            // If the schedule is advanced has no focus allocation, don't change the date
            if($this->type == static::TYPE_ADVANCED && $allocations[$key][$focus] <= 0) {
                return $date;
            }

            // Determine the date for this week
            $thisWeek = carbon()->weekday($key)->startOfDay();

            // Make sure this week comes after the start date
            if($thisWeek->lt($start)) {
                $thisWeek = $thisWeek->addWeek();
            }

            // Return the smaller of the two dates
            return $date->min($thisWeek);

        }, $end);

        // Return the date
        return $date;
    }

    /**
     * Returns the weekday (or total) allocations for each day of the week.
     *
     * @return array
     */
    public function getWeekdayAllocations()
    {
        // If we're using simple allocation, then we can evenly spread
        // the hourly amount to each of the five business days. The
        // math for this should be simple, hence the name. Derp.

        // Check for simple
        if($this->type == static::TYPE_SIMPLE) {

            // Return the total allocation per day
            return [
                0 => 0,
                1 => $this->simple_weekly_allocation / 5 * 3600,
                2 => $this->simple_weekly_allocation / 5 * 3600,
                3 => $this->simple_weekly_allocation / 5 * 3600,
                4 => $this->simple_weekly_allocation / 5 * 3600,
                5 => $this->simple_weekly_allocation / 5 * 3600,
                6 => 0
            ];

        }

        // Determine the days of the week attributes
        $attributes = [
            0 => 'sunday_allocation',
            1 => 'monday_allocation',
            2 => 'tuesday_allocation',
            3 => 'wednesday_allocation',
            4 => 'thursday_allocation',
            5 => 'friday_allocation',
            6 => 'saturday_allocation'
        ];

        // Initialize the data
        $data = array_combine(array_keys($attributes), array_fill(0, count($attributes), []));

        // Determine the allocations
        $allocations = $this->allocations->loadMissing('focusGroup');

        // Iterate through each allocation
        foreach($allocations as $allocation) {

            // Iterate through each attribute
            foreach($attributes as $day => $attribute) {

                // Assign the allocation amount into the data
                $data[$day][$allocation->focusGroup->system_name] = $allocation->getAttribute($attribute);

            }

        }

        // Return the data
        return $data;
    }

    ////////////
    //* Nova *//
    ////////////
    /**
     * Returns the default schedule data for Nova.
     *
     * @return array
     */
    public static function getDefaultScheduleDataForNova()
    {
        return [
            'type' => 'Simple',
            'allocations' => [
                0 => 0,
                1 => 30 / 5 * 3600,
                2 => 30 / 5 * 3600,
                3 => 30 / 5 * 3600,
                4 => 30 / 5 * 3600,
                5 => 30 / 5 * 3600,
                6 => 0,
            ]
        ];
    }

    /**
     * Returns the Nova data for this schedule.
     *
     * @return array
     */
    public function toNovaData()
    {
        return [
            'type' => $this->type,
            'allocations' => $this->getWeekdayAllocations()
        ];
    }

    ///////////////
    //* Queries *//
    ///////////////
    /**
     * Creates and returns a new active schedules query.
     *
     * @param  array  $range
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newActiveSchedulesQuery($range)
    {
        // Start with an issue worklog query
        $query = (new IssueWorklog)->newQuery();

        // Left join into users
        $query->leftJoinRelation('author');

        // Join into schedules
        $query->join('schedules', function($join) {

            // Use a nested "where" clause to contain a disjunction
            $join->where(function($join) {

                // Join using the foreign key
                $join->on('users.schedule_id', '=', 'schedules.id');

                // There's a possibility that someone is logging time, but
                // does not have a user within our application. We will
                // find them, and link them to the default schedule.

                // Use a nested "or where" clause for the fallback
                $join->orWhere(function($join) {

                    // Make sure the user doesn't have a schedule
                    $join->whereNull('users.schedule_id');

                    // Only join into the default schedule
                    $join->where('schedules.system_name', '=', self::SCHEDULE_DEFAULT);

                });

            });

        });

        // Filter the worklogs to the given date range
        $query->whereBetween('issue_worklogs.started_at', $range);

        // Group by the author, schedule, and time
        $query->groupBy([
            'issue_worklogs.author_id',
            'issue_worklogs.author_key',
            'schedules.id',
            'schedules.simple_weekly_allocation'
        ]);

        // Select the grouped columns
        $query->select([
            'issue_worklogs.author_id',
            'issue_worklogs.author_key',
            'schedules.id as schedule_id',
            'schedules.simple_weekly_allocation'
        ]);

        // Return the query
        return $query;
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the allocations associated to this schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allocations()
    {
        return $this->hasMany(ScheduleFocusAllocation::class, 'schedule_id');
    }

    /**
     * Returns the users associated to this schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'schedule_id');
    }
}
