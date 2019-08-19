<template>
    <div>
        <loading-view :loading="initialLoading">
            <div v-if="shouldShowCards">
                <cards
                    v-if="smallCards.length > 0"
                    :cards="smallCards"
                    class="mb-3"
                    :resource-name="resourceName"
                />

                <cards
                    v-if="largeCards.length > 0"
                    :cards="largeCards"
                    size="large"
                    :resource-name="resourceName"
                />
            </div>

            <card class="py-3 flex items-center bg-white border border-50 rounded mb-2">
                <div class="flex items-center justify-between w-full px-3">

                    <div class="flex-1">
                        <div v-if="resources.length" v-text="heading"/>
                    </div>

                    <button
                        v-if="resources.length"
                        class="btn btn-default btn-primary text-white rounded mr-3 h-dropdown-trigger"
                        :class="{ 'btn-disabled': working }"
                        :disabled="working"
                        @click.prevent="saveChanges"
                    >
                        Save Changes
                    </button>

                    <button
                        v-if="resources.length"
                        class="btn btn-link bg-30 px-3 border border-60 rounded mr-3 h-dropdown-trigger cursor-pointer select-none"
                        :class="{ 'btn-disabled': working, 'bg-primary border-primary': orderBy != 'rank' }"
                        :disabled="working"
                        @click.prevent="toggleOrder"
                    >
                        <div class="w-11" :class="{'text-80': orderBy == 'rank', 'text-white': orderBy != 'rank'}">
                            <icon-sort/>
                            <icon-layer-group v-if="orderBy == 'rank'"/>
                            <icon-calendar v-else/>
                        </div>
                    </button>

                    <!-- Filters -->
                    <filter-menu
                        class="border border-60 rounded no-underline"
                        :resource-name="resourceName"
                        :soft-deletes="false"
                        :via-resource="viaResource"
                        :via-has-one="false"
                        trashed=""
                        :per-page="100"
                        :per-page-options="[]"
                        ref="filterMenu"
                        @clear-selected-filters="clearSelectedFilters"
                        @filter-changed="filterChanged"
                    />

                </div>
            </card>

            <card class="bg-white border border-50 shadow p-1">
                <loading-view :loading="loading">
                    <div v-if="resources.length" class="bg-30 border border-50 rounded p-1">
                        <draggable
                            class="-my-2"
                            :class="{ 'dragging': dragging }"
                            :list="resources"
                            ghost-class="ghost"
                            @start="onDragStart"
                            @end="onDragEnd"
                            :component-data="getComponentData()"
                            :forceFallback="true"
                        >
                            <jira-swimlane-issue v-for="(issue, index) in resources" :issue-key="issue.key" :key="issue.key" :index="index" ref="issue"/>
                        </draggable>
                    </div>
                    <div v-else>
                        <h3 class="text-base text-80 font-normal py-3 text-center">
                            No issues matched the given criteria.
                        </h3>
                    </div>
                </loading-view>
            </card>
        </loading-view>
    </div>
</template>

<script>
    import Constants from '../support/constants.js';
    import defaults from 'lodash/defaults';

    import {
        Errors,
        // Deletable,
        Filterable,
        HasCards,
        Minimum,
        // Paginatable,
        // PerPageable,
        InteractsWithQueryString,
        // InteractsWithResourceInformation,
    } from 'laravel-nova';

    export default {
        mixins: [
            // Deletable,
            Filterable,
            HasCards,
            // Paginatable,
            // PerPageable,
            // InteractsWithResourceInformation,
            InteractsWithQueryString,
        ],

        props: [
            'card',

            // The following props are only available on resource detail cards...
            // 'resource',
            // 'resourceId',
            // 'resourceName',
        ],

        data: function() {

            return {
                initialLoading: true,
                loading: true,
                labelsLoading: true,
                working: false,

                resources: [],
                labelData: [],

                orderBy: 'rank',

                resourceName: 'jira-issues',
                viaResource: 'jira-issues',
                selectedActionKey: 'save-swimlane-changes',

                dragging: false,
                schedule: Nova.config.schedule
            };

        },

        /**
         * Mount the component and retrieve its initial data.
         */
        async created() {

            await this.initializeFilters();
            await this.getResources();

            // Mark the initial loading as completed
            this.initialLoading = false;

            this.$watch(
                () => {
                    return (
                        this.encodedFilters
                    )
                },
                () => {
                    this.getResources()
                }
            );

        },

        beforeRouteUpdate(to, from, next) {

            next();
            this.initializeState(false);

        },

        methods: {

            onDragStart: function(ev) {

                this.dragging = true;
                this.draggingComponent = ev.item.__vue__;
                this.draggingComponent.dragging = true;

            },

            onDragEnd: function() {

                this.dragging = false;
                this.draggingComponent.dragging = false;
                this.draggingComponent = null;

                this.resources = this.assignEstimatedCompletionDates(this.resources);

            },

            onDragChange: function(e) {
                //
            },

            getIssue: function(key) {
                return _.find(this.resources, {'key': key});
            },

            /**
             * Get the resources based on the current page, search, filters, etc.
             */
            getResources() {

                this.loading = true;
                this.labelsLoading = true;
                Nova.$emit('resources-loading');

                this.$nextTick(() => {

                    this.resources = [];

                    return Minimum(
                        Nova.request().get('/jira-api/issues', {
                            params: this.resourceRequestQueryString,
                        }),
                        500
                    ).then(({ data }) => {

                        // Reset the resources
                        this.resources = [];
                        this.labelData = [];

                        // Remember the response
                        this.resourceResponse = data;

                        // Determine the raw resources
                        let resources = data.resources;

                        // Assign the estimated completion dates
                        resources = this.assignEstimatedCompletionDates(resources);

                        // Order the issues
                        resources = this.applyOrder(resources);

                        // Update the resources
                        this.resources = resources;

                        // Calculate the label data
                        this.calculateLabelData();

                        this.loading = false;

                        this.$forceUpdate();
                        Nova.$emit('resources-loaded');

                    });

                });

            },

            /**
             * Update the given query string values.
             */
            updateQueryString(value) {

                // Remove the "per page" parameter
                delete value[this.pageParameter];

                // Update the query string
                this.$router.push({ query: defaults(value, this.$route.query) });

            },

            /**
             * Saves the changes made to the issues
             */
            saveChanges() {

                this.working = true;

                Nova.request({
                    method: 'post',
                    url: `/nova-api/${this.resourceName}/action`,
                    params: this.actionRequestQueryString,
                    data: this.actionFormData(),
                })
                    .then(response => {

                        this.handleActionResponse(response.data);
                        this.working = false;

                    })
                    .catch(error => {

                        this.working = false;

                        if(error.response.status == 422) {
                            this.errors = new Errors(error.response.data.errors);
                        }

                    })
            },

            toggleOrder() {

                this.orderBy = (this.orderBy == 'rank' ? 'estimate' : 'rank');

                this.resources = this.applyOrder(this.resources);

            },

            applyOrder(resources) {

                return this.orderBy == 'rank'
                    ? _.orderBy(resources, ['rank'], ['asc'])
                    : _.orderBy(resources, ['new_estimated_completion_date', 'rank'], ['asc', 'asc']);

            },

            /**
             * Gather the action FormData for the given action.
             */
            actionFormData() {

                return _.tap(new FormData(), formData => {

                    formData.append('resources', _.map(this.resources, 'key'));
                    formData.append('resourceData', JSON.stringify(this.getResourceData()));

                });

            },

            getResourceData() {
                return _.map(this.$refs.issue, 'resourceData');
            },

            /**
             * Handle the action response. Typically either a message, download or a redirect.
             */
            handleActionResponse(response) {

                if (response.message) {
                    this.$emit('actionExecuted')
                    this.$toasted.show(response.message, { type: 'success' })
                } else if (response.deleted) {
                    this.$emit('actionExecuted')
                } else if (response.danger) {
                    this.$emit('actionExecuted')
                    this.$toasted.show(response.danger, { type: 'error' })
                } else if (response.download) {
                    let link = document.createElement('a')
                    link.href = response.download
                    link.download = response.name
                    document.body.appendChild(link)
                    link.click()
                    document.body.removeChild(link)
                } else if (response.redirect) {
                    window.location = response.redirect
                } else if (response.openInNewTab) {
                    window.open(response.openInNewTab, '_blank')
                } else {
                    this.$emit('actionExecuted')
                    this.$toasted.show(this.__('The changes were saved successfully!'), { type: 'success' })
                }

            },

            getComponentData() {

                return {
                    on: {
                        change: this.onDragChange
                    }
                }

            },

            /**
             * Assigns estimated complete dates to the issues.
             *
             * @param  {Array}  issues
             *
             * @return {Array}
             */
            assignEstimatedCompletionDates(issues) {

                // Make sure issues have been provided
                if(typeof issues === 'undefined') {
                    return [];
                }

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
                    let focuses = issue['priority_name'] == Constants.PRIORITY_HIGHEST
                        ? [Constants.FOCUS_DEV, Constants.FOCUS_TICKET, Constants.FOCUS_OTHER]
                        : (
                            [Constants.ISSUE_CATEGORY_TICKET, Constants.ISSUE_CATEGORY_DATA].indexOf(issue['issue_category']) >= 0
                                ? [Constants.FOCUS_TICKET]
                                : [Constants.FOCUS_DEV]
                        );

                    // Determine the remaining estimate
                    let remaining = Math.max(issue['estimate_remaining'] || 0, 1 * 60 * 60);

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
                            return date == null ? focusDate : moment(moment.min(date, focusDate));
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
            getFirstAssignmentDate(focus) {

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

            /**
             * Calculates the label data.
             *
             * @return {void{}}
             */
            async calculateLabelData() {

                // Determine the counts for each label
                let counts = _.flatten(_.map(this.resources, (r) => Array.isArray(r.labels) ? r.labels : JSON.parse(r.labels))).reduce(function(counts, label) {
                    return (counts[label] = (counts[label] || 0) + 1) && counts;
                }, {});

                // Since labels within a swimlane are meant to group like
                // issues together, if a label only appears once, we're
                // going to hide it. There's no sense in cluttering.

                // Determine the labels that only appear once
                let once = _.pickBy(counts, (c) => c == 1);

                // Determine the distinct list of labels
                let names = Object.keys(counts).sort();

                // Create a color and shape mapping for each label
                let labels = names.map(function(label, index) {
                    return {
                        'name': label,
                        'color': index % 32,
                        'shape': Math.floor(index / 32),
                        'once': !! once[label]
                    }
                });

                // Determine the label data
                this.labelData = this.resources.reduce(function(data, r) {
                    return (data[r.key] = _.filter(labels, (l) => r.labels.indexOf(l.name) >= 0)) && data;
                }, {});

                this.labelsLoading = false;

            },

            getSwimlane() {
                return this;
            },

            getResourceProvider() {
                return this;
            },

            isLoaded() {
                return !this.loading && !this.initialLoading;
            },

            /**
             * Returns the value of the specified filter name.
             *
             * @param  {string}  name
             *
             * @return {string|null}
             */
            getFilterValue(name) {

                // Determine the filters
                let filters = this.$refs.filterMenu.filters;

                // Determine the specific filter
                let filter = _.find(filters, {'name': name});

                // Determine the current option
                let option = _.find(filter.options, {'value': filter.currentValue});

                // If the option couldn't be found, return null
                if(!option) {
                    return null;
                }

                // Return the option name
                return option.name;

            }

        },

        computed: {

            heading() {

                // Initialize the heading
                let heading = '';

                // Start with the resource count
                heading += this.resources.length;

                // Determine the focus
                let focus = this.getFilterValue('Issue Focus');

                // Add the focus verbiage
                heading += focus ? ' ' + focus + ' ' : '';

                // Add in the "issue" verbiage
                heading += ' ' + (this.resources.length == 1 ? 'Issue' : 'Issues');

                // Determine the assignee
                let assignee = this.getFilterValue('Assignee');

                // Add the assignee verbiage
                heading += ' for ' + (assignee || 'All Users');

                // Return the heading
                return heading;

            },

            /**
             * Get the endpoint for this resource's metrics.
             */
            cardsEndpoint() {
                return `/nova-api/${this.resourceName}/cards`
            },

            /**
             * Get the extra card params to pass to the endpoint.
             */
            extraCardParams() {
                return null;
            },

            /**
             * Get the name of the page query string variable.
             */
            pageParameter() {
                return this.resourceName + '_page'
            },

            /**
             * Build the resource request query string.
             */
            resourceRequestQueryString() {
                return {
                    // search: this.currentSearch,
                    filters: this.encodedFilters,
                    // orderBy: this.currentOrderBy,
                    // orderByDirection: this.currentOrderByDirection,
                    // perPage: this.currentPerPage,
                    // trashed: this.currentTrashed,
                    // page: this.currentPage,
                    // viaResource: this.viaResource,
                    // viaResourceId: this.viaResourceId,
                    // viaRelationship: this.viaRelationship,
                    // viaResourceRelationship: this.viaResourceRelationship,
                    // relationshipType: this.relationshipType,
                }
            },

            /**
             * Get the query string for an action request.
             */
            actionRequestQueryString() {
                return {
                    action: this.selectedActionKey,
                    // pivotAction: this.selectedActionIsPivotAction,
                    // search: this.queryString.currentSearch,
                    filters: this.encodedFilters,
                    // trashed: this.queryString.currentTrashed,
                    // viaResource: this.queryString.viaResource,
                    // viaResourceId: this.queryString.viaResourceId,
                    // viaRelationship: this.queryString.viaRelationship,
                }
            },

            /**
             * Return the currently encoded filter string from the store
             */
            encodedFilters() {
                return this.$store.getters[`${this.resourceName}/currentEncodedFilters`]
            },

            /**
             * Return the initial encoded filters from the query string
             */
            initialEncodedFilters() {
                return this.$route.query[this.filterParameter] || ''
            },

        },

        provide: function() {

            return {
                getIssue: this.getIssue,
                getSwimlane: this.getSwimlane,
                getResourceProvider: this.getResourceProvider,
            };

        },

        mounted() {
            //
        },
    }
</script>
