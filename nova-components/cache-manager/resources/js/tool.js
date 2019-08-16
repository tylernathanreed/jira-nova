Nova.booting((Vue, router, store) => {
    router.addRoutes([
        {
            name: 'cache-manager',
            path: '/cache-manager',
            component: require('./components/Tool'),
        },
    ])
});