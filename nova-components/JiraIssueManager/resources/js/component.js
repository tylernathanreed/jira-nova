import draggable from 'vuedraggable';

Nova.booting((Vue, router, store) => {

    Vue.component('icon-calendar', require('./components/Icons/Calendar'));
    Vue.component('icon-jira', require('./components/Icons/Jira'));
    Vue.component('icon-layer-group', require('./components/Icons/LayerGroup'));
    Vue.component('icon-logout', require('./components/Icons/Logout'));
    Vue.component('icon-sort', require('./components/Icons/Sort'));

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
