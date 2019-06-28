<template>
    <div class="swimlane-issue-wrapper">
        <div class="swimlane-issue" :data-issue="issue.key">
            <div class="swimlane-issue-field" data-field="type">
                <img class="icon" :src="issue.type_icon_url"/>
            </div>

            <div class="swimlane-issue-field" data-field="priority">
                <img class="icon" :src="issue.priority_icon_url"/>
            </div>

            <div class="swimlane-issue-field-group text-center" style="min-width: 80px; max-width: 80px">
                <div class="swimlane-issue-field text-center" data-field="key">
                    <a :href="issue.url" target="_blank" v-text="issue.key"/>
                </div>

                <div v-if="issue.epic_key" :class="'swimlane-issue-field text-center epic-label ' + issue.epic_color" data-field="epic">
                    <a :href="issue.epic_url" target="_blank" v-text="issue.epic_name"/>
                </div>
            </div>

            <div class="swimlane-issue-field" data-field="summary" style="flex: 1; color: #777">
                {{ issue.summary }}
            </div>

            <div :class="'swimlane-issue-field issue-status-' + issue.status_color" data-field="status" style="min-width: 90px; text-align: center">
                {{ issue.status }}
            </div>

            <div class="swimlane-issue-field" data-field="issue-category" style="min-width: 60px; text-align: center">
                {{ issue.issue_category }}
            </div>

            <div class="swimlane-issue-field-group">
                <div class="swimlane-issue-field" data-field="reporter">
                    <div class="flex items-center">
                        <label>R</label>
                        <div class="flex-1 px-1">
                            <img v-if="issue.reporter_icon_url" :src="issue.reporter_icon_url" class="icon rounded-full" />
                            <span v-else class="text-gray">?</span>
                        </div>
                    </div>
                </div>

                <div class="swimlane-issue-field" data-field="assignee">
                    <div class="flex items-center">
                        <label>A</label>
                        <div class="flex-1 px-1">
                            <img v-if="issue.assignee_icon_url" :src="issue.assignee_icon_url" class="icon rounded-full" />
                            <span v-else class="text-gray">?</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="swimlane-issue-field-group">
                <div class="swimlane-issue-field" data-field="due-date" style="min-width: 90px; text-align: center">
                    <div class="flex items-center">
                        <label>D</label>
                        <div class="flex-1">
                            <span
                                :class="due ? '' : 'text-gray'"
                                v-text="due ? date(due).toLocaleDateString() : 'TBD'"
                            />
                        </div>
                    </div>
                </div>

                <div class="swimlane-issue-field" data-field="estimated-completion-date" style="min-width: 90px; text-align: center">
                    <div class="flex items-center">
                        <label>E</label>
                        <div class="flex-1">
                            <span
                                :class="est ? '' : 'text-gray'"
                                v-text="est ? date(est).toLocaleDateString() : 'TBD'"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="swimlane-issue-field" data-field="estimated-offset" style="min-width: 32px; max-width: 32px; text-align: center">
                <span v-if="!due || !est || due == est">&mdash;</span>
                <span v-else-if="offset > 0"
                    class="text-green"
                    v-text="'(+' + (offset > 99 ? '++' : offset) + ')'"
                />
                <span v-else
                    class="text-red"
                    v-text="'(-' + (offset < -99 ? '--' : Math.abs(offset)) + ')'"
                />
            </div>

            <div class="swimlane-issue-field" data-field="time-estimate" style="min-width: 40px; text-align: right">
                {{ (issue.time_estimate / 3600).toFixed(2) }}
            </div>

            <div class="swimlane-issue-field" data-field="links" style="min-width: 50px; max-width: 50px; text-align: center">
                <div v-if="blocks.length > 0">
                    <div class="flex justify-center">
                        <div :class="'block chain-' + blocks[0]['chain']">
                            {{ blocks[0]['depth'] }}
                        </div>

                        <div v-if="blocks[1] && blocks[3]" :class="'block chain-' + blocks[1]['chain']">
                            {{ blocks[1]['depth'] }}
                        </div>
                    </div>

                    <div v-if="blocks[2]" class="flex justify-center">
                        <div v-if="blocks[2] && !blocks[3]" :class="'block chain-' + blocks[1]['chain']">
                            {{ $blocks[1]['depth'] }}
                        </div>

                        <div v-else-if="blocks[2]" :class="'block chain-' + blocks[2]['chain']">
                            {{ $blocks[2]['depth'] }}
                        </div>

                        <div v-if="blocks[3]" :class="'block chain-' + blocks[3]['chain']">
                            {{ $blocks[3]['depth'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {

        props: [
            'issue'
        ],

        methods: {

            /**
             * Converts the specified date string into a date object.
             *
             * @param  {string}  str
             *
             * @return {Date}
             */
            date: function(str) {

                // Determine the date parts
                let parts = str.split('-');

                // Return the new date
                return new Date(parts[0], parts[1], parts[2]);

            }

        },

        computed: {

            due: function() {
                return this.issue.due_date;
            },

            est: function() {
                return this.issue.old_estimated_completion_date;
            },

            offset: function() {

                let a = this.date(this.due);
                let b = this.date(this.est);

                if(!a || !b) {
                    return null;
                }

                let utc1 = Date.UTC(a.getFullYear(), a.getMonth(), a.getDate());
                let utc2 = Date.UTC(b.getFullYear(), b.getMonth(), b.getDate());

                return Math.floor((utc2 - utc1) / (1000 * 60 * 60 * 24));

            },

            blocks: function() {
                return this.issue.blocks;
            }

        }

    }
</script>
