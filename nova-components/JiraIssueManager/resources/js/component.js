import draggable from 'vuedraggable';

Nova.booting((Vue, router, store) => {

    Vue.component('jira-swimlane', require('./components/Swimlane'));
    Vue.component('jira-swimlane-issue', require('./components/SwimlaneIssue'));

    Vue.component('resource-partition-metric', require('./components/Metrics/ResourcePartitionMetric'));    
    Vue.component('resource-trend-metric', require('./components/Metrics/ResourceTrendMetric'));    

    Vue.component('draggable', draggable);

})

Nova.booting((Vue, router, store) => {
    router.addRoutes([
        {
            name: 'jira-issue-manager',
            path: '/jira-issue-manager',
            component: require('./components/Tool'),
        },
    ])
})
