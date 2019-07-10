<template>
    <div class="swimlane-issue-wrapper" :class="{'delinquent': offset <= -7}">

        <input type="hidden" :name="`issues[${index}][key]`" :value="issue.key"/>
        <input type="hidden" :name="`issues[${index}][index]`" :value="index"/>
        <input type="hidden" :name="`issues[${index}][order]`" :value="order"/>
        <input type="hidden" :name="`issues[${index}][est]`" :value="est"/>

        <div class="swimlane-issue" :data-issue="issue.key">
            <div class="swimlane-issue-field">
                <div>{{ index }}</div>
                <div>{{ order }}</div>
            </div>

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
                                v-text="due ? moment(due).toDate().toLocaleDateString() : 'TBD'"
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
                                v-text="est ? moment(est).toDate().toLocaleDateString() : 'TBD'"
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
            'issueKey',
            'index'
        ],

        data: function() {

            return {
                'order': this.index
            }

        },

        methods: {

            moment: window.moment

        },

        computed: {

            issue: function() {
                return this.$parent.$parent.getIssue(this.issueKey);
            },

            due: function() {
                return this.issue.due_date;
            },

            est: function() {
                return this.issue.new_estimated_completion_date || this.issue.old_estimated_completion_date;
            },

            offset: function() {

                if(!this.due || !this.est) {
                    return null;
                }

                let a = moment(this.due);
                let b = moment(this.est);

                return a.diff(b, 'days', 0);

            },

            blocks: function() {
                return this.issue.blocks;
            }

        }

    }
</script>

<style>
    .swimlane-issue-wrapper {
        display: flex;
        align-items: center;
        min-height: 3rem;
        width: 100%;
        margin: 5px 0;
        padding: 5px;
        font-size: 12px;
        font-family: 'Segoe UI';
        line-height: 1rem;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 3px;
        box-shadow: 0 1px 2px 0 rgba(9, 30, 66, 0.25);
        color: #333;
        cursor: move;
        user-select: none;
    }

    .swimlane-issue-wrapper.delinquent {
        background: #fee;
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

    .epic-label a,
    .epic-label a:active,
    .epic-label a:hover,
    .epic-label a:focus {
        color: inherit;
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

    img.icon {
        width: 16px;
        height: 16px;
    }

    .text-gray {
        color: #aaa;
    }

    .flex {
        display: flex;
    }

    .items-center {
        align-items: center;
    }

    .space-between {
        justify-content: space-between;
    }

    .justify-center {
        justify-content: center;
    }

    .flex-1 {
        flex: 1;
    }

    .text-center {
        text-align: center;
    }

    .text-green {
        color: #008800;
    }

    .text-red {
        color: #ff0000;
    }

    .block {
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

    .chain-0 { background-color: black; }
    .chain-1 { background-color: red; }
    .chain-2 { background-color: steelblue; }
    .chain-3 { background-color: green; }
    .chain-4 { background-color: darkorange; }
    .chain-5 { background-color: blueviolet; }
    .chain-6 { background-color: lightseagreen; }
    .chain-7 { background-color: hotpink; }
    .chain-8 { background-color: yellow; }
    .chain-9 { background-color: lime; }
    .chain-10 { background-color: dimgray; }
    .chain-11 { background-color: sienna; }
    .chain-12 { background-color: olive; }
    .chain-13 { background-color: darkslategray; }
    .chain-14 { background-color: lightgray; }
    .chain-15 { background-color: rosybrown; }
    .chain-16 { background-color: darkseagreen; }
    .chain-17 { background-color: tan; }

    label {
        margin: 0;
    }

    .px-1 {
        padding-left: 0.25rem;
        padding-right: 0.25rem;
    }

    .rounded-full {
        border-radius: 9999px;
    }

    a {
        color: #0052cc;
        text-decoration: none;
    }

    a:active, a:hover, a:focus {
        color: rgb(0, 73, 176);
        text-decoration: underline;
    }
</style>