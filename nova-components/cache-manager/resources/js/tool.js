Nova.booting((Vue, router, store) => {
    router.addRoutes([
        {
            name: 'cache-manager',
            path: '/cache-manager',
            component: require('./components/Tool'),
        },
    ])
});

window.refreshNovaData = function() {

    // Determine all of the components
    let components = getDescendants(Nova.app);
    let elements = Nova.app.$children;

};

window.getDescendants = function(component) {

    // Initialize the list
    let descendants = [];

    // Determine the children of the component
    let children = descendants = component.$children;

    // Add the descendants of each child to the list
    _.each(children, function(child) {
        _.each(getDescendants(child), function(descendant) {
            descendants.push(descendant);
        })
    });

    // Return the descendants
    return descendants;

};