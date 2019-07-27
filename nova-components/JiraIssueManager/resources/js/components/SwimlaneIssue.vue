<template>
    <div :class="'swimlane-issue-focus mr-3 my-2 rounded focus-' + issue.focus.toLowerCase()">
        <div
            class="p-2 ml-3 flex items-center border rounded rounded-l-none min-h-input w-full text-90 text-xs leading-rem font-segoe-ui shadow-sm cursor-move select-none"
            :class="{
                'bg-white border-50': !dragging && (!est || offset > -7),
                'bg-delinquent border-delinquent hover:bg-delinquent-light': !dragging && (offset <= -7),
                'hover:bg-20': !dragging && (!est || offset > -7) && !getSwimlane().dragging,
                'bg-50 border-60': dragging
            }"
        >
            <div class="swimlane-issue" :data-issue="issue.key">

                <div class="swimlane-issue-field" data-field="type">
                    <img class="icon" :src="issue.type_icon_url"/>
                </div>

                <div class="swimlane-issue-field" data-field="priority">
                    <img class="icon" :src="issue.priority_icon_url"/>
                </div>

                <div class="swimlane-issue-field-group text-center" style="min-width: 80px; max-width: 80px">
                    <div class="swimlane-issue-field text-center" data-field="key">
                        <a :href="issue.parent_url || issue.url" target="_blank" v-text="issue.parent_key || issue.key"/>
                    </div>

                    <div v-if="issue.epic_key" :class="'swimlane-issue-field text-center epic-label ' + (issue.epic_color || 'ghx-label-0')" data-field="epic">
                        <a :href="issue.epic_url" target="_blank" v-text="issue.epic_name"/>
                    </div>
                </div>

                <div class="swimlane-issue-field" data-field="summary" style="flex: 1; color: #777">
                    <span v-if="issue.is_subtask">
                        / <a :href="issue.url" target="_blank" v-text="issue.key"/> /
                    </span>

                    {{ issue.summary }}
                </div>

                <div :class="'swimlane-issue-field issue-status-' + issue.status_color" data-field="status" style="min-width: 90px; text-align: center">
                    {{ issue.status_name }}
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
                                    v-text="due ? moment(due).toDate().toLocaleDateString() : 'TBD'"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="swimlane-issue-field" data-field="estimated-completion-date" style="min-width: 90px; text-align: center">
                        <div class="flex items-center">
                            <label>E</label>
                            <div class="flex-1">
                                <span v-if="est" v-text="moment(est).toDate().toLocaleDateString()"/>
                                <loader v-else class="text-gray" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="swimlane-issue-field" data-field="estimated-offset" style="min-width: 32px; max-width: 32px; text-align: center">
                    <span v-if="!due || !est || due == est">&mdash;</span>
                    <span v-else-if="offset > 0"
                        class="text-success"
                        v-text="'(+' + (offset > 99 ? '++' : offset) + ')'"
                    />
                    <span v-else
                        class="text-danger"
                        v-text="'(-' + (offset < -99 ? '--' : Math.abs(offset)) + ')'"
                    />
                </div>

                <div class="swimlane-issue-field" data-field="time-estimate" style="min-width: 40px; text-align: right">
                    {{ (issue.estimate_remaining / 3600).toFixed(2) }}
                </div>

                <div class="swimlane-issue-field" data-field="links" style="min-width: 50px; max-width: 50px; text-align: center">
                    <div v-if="blocks.length > 0">
                        <div class="flex justify-center">
                            <div :class="'link-block bg-range-' + blocks[0]['chain']">
                                {{ blocks[0]['depth'] }}
                            </div>

                            <div v-if="blocks[1] && blocks[3]" :class="'link-block bg-range-' + blocks[1]['chain']">
                                {{ blocks[1]['depth'] }}
                            </div>
                        </div>

                        <div v-if="blocks[2]" class="flex justify-center">
                            <div v-if="blocks[2] && !blocks[3]" :class="'link-block bg-range-' + blocks[1]['chain']">
                                {{ $blocks[1]['depth'] }}
                            </div>

                            <div v-else-if="blocks[2]" :class="'link-block bg-range-' + blocks[2]['chain']">
                                {{ $blocks[2]['depth'] }}
                            </div>

                            <div v-if="blocks[3]" :class="'link-block bg-range-' + blocks[3]['chain']">
                                {{ $blocks[3]['depth'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import moment from 'moment';

    export default {

        props: [
            'issueKey',
            'index',
            'swimlane'
        ],

        data: function() {

            return {
                'order': this.index,
                'dragging': false
            }

        },

        methods: {

            moment: moment

        },

        inject: [
            'getIssue',
            'getSwimlane'
        ],

        computed: {

            issue: function() {
                return this.getIssue(this.issueKey);
            },

            due: function() {
                return this.issue.due_date;
            },

            est: function() {
                return this.issue.new_estimated_completion_date;
            },

            offset: function() {

                if(!this.due || !this.est) {
                    return null;
                }

                let a = this.moment(this.due);
                let b = this.moment(this.est);

                return a.diff(b, 'days', 0);

            },

            blocks: function() {
                return this.issue.blocks;
            },

            resourceData: function() {

                return {
                    'key': this.issue.key,
                    'order': this.index,
                    'assignee': this.issue.assignee_name,
                    'est': this.est,
                    'due': this.due,
                    'focus': this.issue.focus,
                    'remaining': this.issue.estimate_remaining,
                    'priority': this.issue.priority_name,
                    'is_subtask': this.issue.is_subtask ? 1 : 0,
                    'parent_key': this.issue.parent_key,
                    'rank': this.issue.rank,
                    'original': {
                        'order': this.order,
                        'est': this.issue.estimate_date
                    }
                };

            }

        }

    }
</script>

<style>
    .shadow-sm {
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.25);
    }

    .font-segoe-ui {
        font-family: 'Segoe UI';
    }

    .leading-rem {
        line-height: 1rem;
    }

    .bg-delinquent {
        background: #fee;
    }

    .hover\:bg-delinquent-light:hover {
        background: #fff4f4;
    }

    .border-delinquent {
        border-color: #daa;
    }

    .swimlane-content:not(.dragging) .swimlane-issue-wrapper:hover {
        background-color: #f8f8ff;
    }

    .swimlane-content:not(.dragging) .swimlane-issue-wrapper.delinquent:hover {
        background-color: #fff4f4;
    }

    .swimlane-issue {
        display: flex;
        align-items: center;
        margin: 0 -3px;
        width: 100%;
        transition: transform 0.5s;
    }

    .swimlane-issue-field {
        padding: 0 3px;
    }

    .issue-status-blue-gray {
        display: inline-block;
        padding: 1px 4px;
        font-size: 10px;
        font-weight: bold;
        border-width: 1px;
        border-style: solid;
        border-radius: 3px;

        background: #fff;
        color: #43526e;
        border-color: #c1c7d0;
    }

    .issue-status-yellow {
        display: inline-block;
        padding: 1px 4px;
        font-size: 10px;
        font-weight: bold;
        border-width: 1px;
        border-style: solid;
        border-radius: 3px;

        background: #fff;
        color: #0052cc;
        border-color: #b3d4ff;
    }

    .epic-label {
        display: inline-block;
        border-radius: 3px;
        font-size: 12px;
        font-weight: normal;
        line-height: 1;
        padding-top: 1px;
        padding-left: 5px;
        padding-right: 5px;
        padding-bottom: 2px;
        margin-left: 3px;
        margin-right: 3px;
    }

    .swimlane-issue .epic-label a,
    .swimlane-issue .epic-label a:active,
    .swimlane-issue .epic-label a:hover,
    .swimlane-issue .epic-label a:focus {
        color: inherit;
    }

    .ghx-label-0 {
        color: #0065ff;
        background-color: #f5f5f5;
        border-color: #ccc;
        border-width: 1px;
    }

    .ghx-label-2 {
        color: #172B4D;
        background-color: #ffc400;
        border-color: #ffc400;
    }

    .ghx-label-4 {
        color: #fff;
        background-color: #2684ff;
        border-color: #2684ff;
    }

    .ghx-label-6 {
        color: #42526e;
        background-color: #abf5d1;
        border-color: #abf5d1;
    }

    .ghx-label-7 {
        color: #fff;
        background-color: #8777d9;
        border-color: #8777d9;
    }

    .ghx-label-9 {
        color: #fff;
        background-color: #ff7452;
        border-color: #ff7452;
    }

    .ghx-label-11 {
        color: #42526e;
        background-color: #79e2f2;
        border-color: #79e2f2;
    }

    .ghx-label-12 {
        color: #fff;
        background-color: #7a869a;
        border-color: #7a869a;
    }

    .ghx-label-14 {
        color: #fff;
        background-color: #ff8f73;
        border-color: #ff8f73;
    }

    .focus-other {
        background: #94c4fe;
        background: linear-gradient(135deg, #cc0000 33.33%, #990000 33.33%, #990000 50%, #cc0000 50%, #cc0000 83.33%, #990000 83.33%, #990000 100%);
        background-size: 4.24px 4.24px;
        box-shadow: inset 0 0 1px rgba(0, 0, 0, 0.5), inset 0 0 2px rgba(0, 0, 0, 0.5);
    }

    .focus-dev {
        background: #94c4fe;
        background: linear-gradient(135deg, #5b9bd5 33.33%, #2f76b5 33.33%, #2f76b5 50%, #5b9bd5 50%, #5b9bd5 83.33%, #2f76b5 83.33%, #2f76b5 100%);
        background-size: 4.24px 4.24px;
        box-shadow: inset 0 0 1px rgba(0, 0, 0, 0.5), inset 0 0 2px rgba(0, 0, 0, 0.5);
    }

    .focus-ticket {
        background: #94c4fe;
        background: linear-gradient(135deg, #ffc000 33.33%, #bf8f00 33.33%, #bf8f00 50%, #ffc000 50%, #ffc000 83.33%, #bf8f00 83.33%, #bf8f00 100%);
        background-size: 4.24px 4.24px;
        box-shadow: inset 0 0 1px rgba(0, 0, 0, 0.5), inset 0 0 2px rgba(0, 0, 0, 0.5);
    }

    .swimlane-issue img.icon {
        width: 16px;
        height: 16px;
    }

    .text-gray {
        color: #aaa;
    }

    .link-block {
        width: 16px;
        height: 16px;
        margin: 1px;
        font-size: 10px;
        line-height: 12px;
        font-weight: bold;
        border: 1px solid black;
        color: white;
        text-shadow:
             0px  0px 1px black,
             0px  1px 1px black,
             0px -1px 1px black,
             1px  0px 1px black,
             1px  1px 1px black,
             1px -1px 1px black,
            -1px  0px 1px black,
            -1px  1px 1px black,
            -1px -1px 1px black;

        background-color: white;
    }

    .bg-range-0 { background-color: red; }
    .bg-range-1 { background-color: yellow; }
    .bg-range-2 { background-color: blue; }
    .bg-range-3 { background-color: orange; }
    .bg-range-4 { background-color: green; }
    .bg-range-5 { background-color: darkmagenta; }
    .bg-range-6 { background-color: lime; }
    .bg-range-7 { background-color: cyan; }
    .bg-range-8 { background-color: magenta; }
    .bg-range-9 { background-color: #faa; }
    .bg-range-10 { background-color: khaki; }
    .bg-range-11 { background-color: #cbf; }
    .bg-range-12 { background-color: tan; }
    .bg-range-13 { background-color: greenyellow; }
    .bg-range-14 { background-color: mediumorchid; }
    .bg-range-15 { background-color: #cfc; }
    .bg-range-16 { background-color: #8cf; }
    .bg-range-17 { background-color: #fdf; }
    .bg-range-18 { background-color: firebrick; }
    .bg-range-19 { background-color: darkgoldenrod; }
    .bg-range-20 { background-color: cornflowerblue; }
    .bg-range-21 { background-color: #c60; }
    .bg-range-22 { background-color: olive; }
    .bg-range-23 { background-color: darkslateblue; }
    .bg-range-24 { background-color: mediumseagreen; }
    .bg-range-25 { background-color: #08a; }
    .bg-range-26 { background-color: #f4a; }
    .bg-range-27 { background-color: black; }
    .bg-range-28 { background-color: darkslategray; }
    .bg-range-29 { background-color: #888; }
    .bg-range-30 { background-color: silver; }
    .bg-range-31 { background-color: white; }

    .swimlane-issue label {
        margin: 0;
    }

    .swimlane-issue a {
        color: #0052cc;
        text-decoration: none;
    }

    .swimlane-issue a:active,
    .swimlane-issue a:hover,
    .swimlane-issue a:focus {
        color: rgb(0, 73, 176);
        text-decoration: underline;
    }
</style>