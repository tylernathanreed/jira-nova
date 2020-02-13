// Add a bootstrapper for liftoff
Nova.booting(function(Vue, router, store) {

    // Register the callbacks to be invoked after the router resolves each page
    router.afterEach(() => {

        // Replace the title
        replaceTitleUsingRoute();

        // Qualify the name of custom dashboards
        qualifyCustomDashboardNames();

    });

});

var replaceTitleUsingRoute = function() {

    // Determine the path that comes after the base title
    let path = Nova.app._route.path.replace(/^\//, '').replace(/\//g, ' :: ').replace(/-/g, ' ').toLowerCase().replace(/(?<= )[^\s]|^./g, s => s.toUpperCase());

    // Append the path to the base title
    if(path) {
        document.title = Nova.config.name + ' :: Week ' + Nova.config.weekIndex + ' :: ' + path;
    } else {
        document.title = Nova.config.name;
    }

};

var qualifyCustomDashboardNames = function() {

    // Determine the route
    let route = Nova.app._route;

    // Make sure we're viewing a custom dashboard
    if(route.name != 'dashboard.custom') {
        return;
    }

    // Determine the dashboard name
    let name = route.params.name;

    // Make sure we're not looking at the main dashboard
    if(name == 'main') {

        // Make sure the translation is unhijacked before we bail
        Nova.config.translations.Dashboard = 'Dashboard';

        // Bail
        return;

    }

    // Determine the label for the dashboard
    let label = name.replace(/-/g, ' ').replace(/\b[a-z]/g, (s) => s.toUpperCase());

    // Hijack the translation for the word "Dashboard"
    Nova.config.translations.Dashboard = label;

};