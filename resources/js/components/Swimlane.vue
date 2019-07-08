<template>
    <div class="swimlane">
        <draggable
            class="swimlane-content"
            :class="{ 'dragging': dragging }"
            :list="issues"
            ghost-class="ghost"
            @start="onDragStart"
            @end="onDragEnd"
            :component-data="getComponentData()"
        >
            <swimlane-issue v-for="(issue, index) in issues" :issue-key="issue.key" :key="issue.key" :index="index"/>
        </draggable>
    </div>
</template>

<script>
    import Constants from '../support/constants.js';

    export default {

        props: [
            'issues'
        ],

        data: function() {

            return {
                updatedIssues: this.issues,
                dragging: false,
                schedule: {
                    0: {[Constants.FOCUS_DEV]: 0,             [Constants.FOCUS_TICKET]: 0,             [Constants.FOCUS_OTHER]: 0},
                    1: {[Constants.FOCUS_DEV]: 4.5 * 60 * 60, [Constants.FOCUS_TICKET]: 0,             [Constants.FOCUS_OTHER]: 3.5 * 60 * 60 * 0.5},
                    2: {[Constants.FOCUS_DEV]: 0,             [Constants.FOCUS_TICKET]: 5 * 60 * 60,   [Constants.FOCUS_OTHER]: 3 * 60 * 60 * 0.5},
                    3: {[Constants.FOCUS_DEV]: 5 * 60 * 60,   [Constants.FOCUS_TICKET]: 0,             [Constants.FOCUS_OTHER]: 3 * 60 * 60 * 0.5},
                    4: {[Constants.FOCUS_DEV]: 0,             [Constants.FOCUS_TICKET]: 4.5 * 60 * 60, [Constants.FOCUS_OTHER]: 3.5 * 60 * 60 * 0.5},
                    5: {[Constants.FOCUS_DEV]: 5 * 60 * 60,   [Constants.FOCUS_TICKET]: 0,             [Constants.FOCUS_OTHER]: 3 * 60 * 60 * 0.5},
                    6: {[Constants.FOCUS_DEV]: 0,             [Constants.FOCUS_TICKET]: 0,             [Constants.FOCUS_OTHER]: 0}

                }
            };

        },

        methods: {

            onDragStart: function() {
                this.dragging = true;
            },

            onDragEnd: function() {

                this.dragging = false;

                this.updatedIssues = this.assignEstimatedCompletionDates(this.issues);

            },

            onDragChange: function(e) {
                //
            },

            getIssue: function(key) {
                return _.find(this.updatedIssues, {'key': key});
            },

            /**
             * Assigns estimated complete dates to the issues.
             *
             * @param  {Array}  issues
             *
             * @return {Array}
             */
            assignEstimatedCompletionDates: function(issues) {

                // Our schedule is broken down into focus times. Issues can be allocated
                // to one or more focuses, and these focus times are when we can work
                // on these issues. We ought to respect the focus in the schedule.

                // Initialize the dates for each focus
                let dates = {
                    [Constants.FOCUS_DEV]: this.getFirstAssignmentDate(Constants.FOCUS_DEV),
                    [Constants.FOCUS_TICKET]: this.getFirstAssignmentDate(Constants.FOCUS_TICKET),
                    [Constants.FOCUS_OTHER]: this.getFirstAssignmentDate(Constants.FOCUS_OTHER)
                };

                // Determine the schedule
                let schedule = this.schedule;

                // Remap the issues
                return issues.map(function(issue) {

                    // Determine the issue focus
                    let focuses = issue['priority'] == Constants.PRIORITY_HIGHEST
                        ? [Constants.FOCUS_DEV, Constants.FOCUS_TICKET, Constants.FOCUS_OTHER]
                        : (
                            [Constants.ISSUE_CATEGORY_TICKET, Constants.ISSUE_CATEGORY_DATA].indexOf(issue['issue_category']) >= 0
                                ? [Constants.FOCUS_TICKET]
                                : [Constants.FOCUS_DEV]
                        );

                    // Determine the remaining estimate
                    let remaining = Math.max(issue['time_estimate'] || 0, 1 * 60 * 60);

                    // Since an issue on its own can take longer than a day to complete,
                    // we essentially have to chip away at the remaining estimate so
                    // that we can correctly spread the work effort into many days.

                    // Initialize the date
                    let date = null;

                    // Allocate the remaining estimate in a time loop until its all gone
                    while(remaining > 0) {

                        // Determine the applicable focus dates
                        let focusDates = _.pick(dates, focuses);

                        // Determine the earliest focus date
                        date = Object.values(focusDates).reduce(function(date, focusDate) {
                            return date == null ? focusDate : moment.min(date, focusDate);
                        }, null);

                        // Determine the focus with that date
                        let focus = _.last(focuses.filter(function(focus) {
                            return focusDates[focus].isSame(date);
                        }));

                        // Determine how much time as already been allocated for the day
                        let allocated = (date.get('hour') * 60 + date.get('minute')) * 60 + date.get('second');

                        // Determine the daily focus limit
                        let limit = schedule[date.weekday()][focus];

                        // If the previous issue ended cleanly on the exact amount of allocatable
                        // time, we wanted it to end on that date. However, we have to advance
                        // to the next day for the next issue, otherwise we'll loop forever.

                        // Check if we've run out of time for the day
                        if(allocated >= limit) {

                            // Advance to the next day
                            date = date.add(1, 'day').startOf('day');

                            // Update the focus date
                            dates[focus] = date;

                            // Try again
                            continue;

                        }

                        // Determine how much time we can allocate for today
                        let allocatable = Math.min(remaining, limit - allocated);

                        // Allocate the time
                        date = date.add(allocatable, 'second');

                        // Reduce the remaining time by how much was allocated
                        remaining -= allocatable;

                        // If we have exceeded the daily limit, advance to the next day
                        if(allocated + allocatable > limit) {

                            date = date.add(1, 'day').startOf('day');

                        }

                        // Skip dates that have no allocatable time
                        while(schedule[date.weekday()][focus] <= 0) {
                            date = date.add(1, 'day');
                        }

                        // Update the focus date
                        dates[focus] = date;
                    }

                    // Assign the estimated completion date
                    issue['new_estimated_completion_date'] = date.format('YYYY-MM-DD');

                    // Return the issue
                    return issue;

                });

            },

            /**
             * Returns the first assignment date for the schedule.
             *
             * @param  {string}  focus
             *
             * @return {Date}
             */
            getFirstAssignmentDate: function(focus) {

                // Determine the schedule
                let schedule = this.schedule;

                // Until we have a better scheduling concept, we're going to
                // base everything off of the default schedule, and probit
                // issues from being scheduled same-day after 11:00 AM.

                // Determine the soonest we can start scheduling
                let start = moment().isBefore(moment().startOf('day').add(11, 'hours')) // If it's prior to 11 AM
                    ? moment().startOf('day') // Start no sooner than today
                    : moment().add(1, 'day').startOf('day'); // Otherwise, start no sooner than tomorrow

                // Determine the latest we can start scheduling
                let end = moment().add(8, 'days').startOf('day'); // Start no later than a week after tomorrow

                // Determine the first date where we can start assigning due dates
                let date = Object.keys(schedule).reduce(function(date, key) {

                    // If the schedule has no focus allocation, don't change the date
                    if(schedule[key][focus] <= 0) {
                        return date;
                    }

                    // Determine the date for this week
                    let thisWeek = moment().weekday(key).startOf('day');

                    // Make sure this week comes after the start date
                    if(thisWeek.isBefore(start)) {
                        thisWeek = thisWeek.add(1, 'week');
                    }

                    // Use the smaller of the two dates
                    return moment.min(date, thisWeek);

                }, end);

                // Return the date
                return date;

            },

            getComponentData() {

                return {
                    on: {
                        change: this.onDragChange
                    }
                }

            }

        }

    }
</script>

<style>
    .swimlane-content {
        list-style-type: none;
        margin: 0;
        padding: 0 5px;
        border: 1px solid #ddd;
        border-radius: 3px;
        background-color: #f4f4f4;
        width: 100%;
    }

    .swimlane-placeholder, .ghost {
        height: 2rem;
        border: 1px dashed #aaa;
        border-radius: 3px;
        background-color: rgba(0, 0, 0, 0.05);
        opacity: 1;
        z-index: 50;
    }

    .sortable-drag {
       opacity: 0;
    }
</style>