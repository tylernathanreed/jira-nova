Nova.booting((Vue, router, store) => {
    Vue.component('slideshow-player', require('./components/SlideshowPlayer'));
});

Nova.booting((Vue, router, store) => {

    router.addRoutes([
        {
            name: 'slideshow-player',
            path: '/slideshows/:resourceId/play',
            component: require('./components/SlideshowPlayer'),
            props: true
        },
    ]);

});