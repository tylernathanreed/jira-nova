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
                        @clear-selected-filters="onClearSelectedFilters"
                        @filter-changed="onFilterChanged"
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
                scheduleType: Nova.config.schedule.type,
                allocations: Nova.config.schedule.allocations
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

                this.$nextTick(() => {
                    this.assignEstimatedCompletionDates();
                });

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

                        // Determine the resources
                        let resources = data.resources;

                        // Order the issues
                        resources = this.applyOrder(resources);

                        // Update the resources
                        this.resources = resources;

                        this.$forceUpdate();

                        let self = this;

                        // Assign the estimated completion dates (this happens asynchronously)
                        this.assignEstimatedCompletionDates(function() {

                            // Order the issues again
                            self.resources = self.applyOrder(self.resources);

                            // Mark the resources as loaded
                            self.loading = false;

                            // Let everyone know the resources are ready
                            Nova.$emit('resources-loaded');

                        });

                        // Calculate the label data
                        this.calculateLabelData();

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

                return this.newFormData({
                    resources: _.map(this.resources, 'key'),
                    resourceData: JSON.stringify(this.getResourceData())
                });

            },

            /**
             * Creates and returns new form data using the specified data.
             *
             * @param  {object}  data
             *
             * @return {FormData}
             */
            newFormData(data) {

                // Create a new form data instance
                let formData = new FormData();

                // Add each data element to the form data
                _.each(data, function(value, key) {
                    formData.append(key, value);
                });

                // Return the form data
                return formData;

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
             * Assigns estimated completion dates to the issues.
             *
             * @param  {Function|null}  after
             *
             * @return {void}
             */
            assignEstimatedCompletionDates(after = null) {

                let self = this;

                // Clear the estimate dates
                _.each(this.resources, function(issue) {
                    issue['estimate'] = null;
                });

                // Update the children
                _.each(this.$refs.issue, function(child) {
                    child.setEstimate(null);
                    // child.$forceUpdate();
                });

                // Determine the issues
                let issues = this.resources.map(function(issue, index) {
                    return {
                        'key': issue.key,
                        'order': index,
                        'assignee': issue.assignee_key,
                        'focus': issue.focus,
                        'remaining': issue.estimate_remaining
                    };
                })

                Nova.request({
                    method: 'post',
                    url: '/nova-vendor/jira-priorities/estimate',
                    data: this.newFormData({'issues': JSON.stringify(issues)}),
                })
                    .then(response => {

                        // Iterate through the estimates
                        _.each(response.data.estimates, function(estimate) {

                            // Find the associated issue
                            let issue = _.find(self.resources, {key: estimate.key});

                            // Find the associated child component
                            let child = _.find(self.$refs.issue, {issueKey: estimate.key});

                            // If we found the issue, update the estimate
                            if(issue && issue.estimate != estimate.estimate) {

                                // Update the estimate
                                issue.estimate = estimate.estimate;

                                // Update the child
                                if(child) {
                                    child.setEstimate(issue.estimate);
                                }

                            }

                        });

                        // Invoke the after callback
                        if(after) {
                            after();
                        }

                    })
                    .catch(error => {

                        if(!error.response) {

                            console.warn(error);
                            this.errors = new Errors(error.message);

                        }

                        else if(error.response.status == 422) {
                            this.errors = new Errors(error.response.data.errors);
                        }

                    })


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

            },

            onFilterChanged() {
                this.filterChanged();
                Nova.$emit('filter-changed');
            },

            onClearSelectedFilters() {
                this.clearSelectedFilters();
                Nova.$emit('filter-changed');
            }

        },

        computed: {

            heading() {

                // Initialize the heading
                let heading = '';

                // Start with the resource count
                heading += this.resources.length;

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
