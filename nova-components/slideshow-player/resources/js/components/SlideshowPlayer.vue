<template>
    <loading-view :loading="initialLoading">
        <div class="overflow-hidden">
            <iframe :src="currentUrl" class="slideshow-page w-screen h-screen -mb-1" :style="{opacity: pageOpacity}" ref="page"></iframe>
        </div>
        <div
            v-if="showTimer"
            class="page-timer bg-primary"
            :style="{
                width: `${pagePercent}%`,
                height: '3px'
            }"
        />
    </loading-view>
</template>

<style scoped>
    .slideshow-page {
        transition: opacity 1.5s;
    }

    .page-timer {
        position: absolute;
        left: 0px;
        right: 0px;
        bottom: 0px;
        height: 2px;
        width: 0%;
        transition: width 0.2s;
        z-index: 999999;
    }
</style>

<script>
    import {
        InteractsWithResourceInformation,
        Minimum,
    } from 'laravel-nova'

    export default {
        props: ['resourceId'],

        mixins: [InteractsWithResourceInformation],

        data: () => ({

            initialLoading: true,
            loading: true,

            resource: null,
            resourceName: 'slideshows',

            pages: [],

            pagePercent: 0,
            pageOpacity: 1,
            pageDuration: 5 * 60 * 1000,
            pageIndex: null,

            scrollTo: 0,
            scrollDirection: 1,

            showTimer: false

        }),

        watch: {
            resourceId: function(newResourceId, oldResourceId) {
                if (newResourceId != oldResourceId) {
                    this.initializeComponent()
                }
            },
        },

        /**
         * Bind the keydown even listener when the component is created
         */
        created() {
            this.enableFullscreen();
        },

        /**
         * Mount the component.
         */
        mounted() {
            this.initializeComponent()
        },

        methods: {

            enableFullscreen() {

                document.documentElement.classList.remove('nova-fullscreen-disabled');
                document.documentElement.classList.add('nova-fullscreen-enabled');
                document.documentElement.classList.add('slideshow-enabled');

            },

            /**
             * Initialize the compnent's data.
             */
            async initializeComponent() {

                await this.getResource();
                await this.setTheme();
                await this.getSlideshowPages();
                await this.startPageTimer();

                this.initialLoading = false;

            },

            /**
             * Get the resource information.
             */
            getResource() {
                this.resource = null

                return Minimum(
                    Nova.request().get('/nova-api/' + this.resourceName + '/' + this.resourceId)
                )
                    .then(({ data: { panels, resource } }) => {
                        this.resource = resource
                        this.loading = false
                    })
                    .catch(error => {
                        if (error.response.status >= 500) {
                            Nova.$emit('error', error.response.data.message)
                            return
                        }

                        if (error.response.status === 404 && this.initialLoading) {
                            this.$router.push({ name: '404' })
                            return
                        }

                        if (error.response.status === 403) {
                            this.$router.push({ name: '403' })
                            return
                        }

                        this.$toasted.show(this.__('This resource no longer exists'), { type: 'error' })

                        this.$router.push({
                            name: 'index',
                            params: { resourceName: this.resourceName },
                        })
                    })
            },

            setTheme() {

                let theme = _.find(this.resource.fields, {attribute: 'theme'}).value;

                document.documentElement.classList.remove('nova-default-theme');
                document.documentElement.classList.add(`nova-${theme}-theme`);

            },

            /**
             * Returns the pages for the slideshow.
             *
             * @return {Array}
             */
            getSlideshowPages() {

                return Minimum(
                    Nova.request().get('/nova-api/slideshow-pages', {
                        params: this.slideshowPagesRequestQueryString,
                    }),
                    300
                ).then(({ data }) => {
                    this.pages = data.resources;
                    this.pageIndex = 0;
                })
            },

            startPageTimer() {

                this.showTimer = true;

                if(this._pageTimer) {

                    clearInterval(this._pageTimer);
                    this.pagePercent = 0;

                }

                this._pageCut = 100 / (this.pageDuration / 100);

                this._pageTimer = setInterval(() => {
                    this.tickPageTimer();
                }, 100);

                setTimeout(() => {
                    setTimeout(() => {
                        this.startScrollTimer();
                    }, this.getScrollInterval() - 5000);
                }, 5000)

            },

            tickPageTimer() {

                this.increasePageTimer(this._pageCut);

                if (this.pagePercent >= 100) {
                    this.finishPageTimer();
                }

            },

            increasePageTimer(num) {
                this.pagePercent += num;
            },

            finishPageTimer() {

                this.pagePercent = 0;
                this.pausePageTimer();
                this.pageOpacity = 0;

                this.pauseScrollTimer();
                this.scrollDirection = 1;
                this.scrollTo = 0;

                setTimeout(() => {
                    this.pageIndex = (this.pageIndex + 1) % this.pages.length;
                }, 500);

                setTimeout(() => {
                    this.pageOpacity = 1;
                }, 3500);

                setTimeout(() => {
                    this.startPageTimer();
                }, 5000);

            },

            pausePageTimer() {
                clearInterval(this._pageTimer);
            },

            startScrollTimer() {

                if(this._scrollTimer) {
                    clearInterval(this._scrollTimer);
                }

                this._scrollStep = this.getScrollLimit() / (this.getScrollInterval() / 100);

                this._scrollTimer = setInterval(() => {
                    this.tickScrollTimer();
                }, 100);

            },

            tickScrollTimer() {

                this.increaseScrollTimer(this._scrollStep);

                if(this.scrollTo >= this.getScrollLimit() || this.scrollTo < 0) {
                    this.finishScrollTimer();
                }

            },

            increaseScrollTimer(num) {

                this.scrollTo += num * this.scrollDirection;
                this.$refs.page.contentWindow.scrollTo(0, this.scrollTo);

            },

            finishScrollTimer() {

                this.pauseScrollTimer();
                this.scrollDirection *= -1;

                setTimeout(() => {
                    this.startScrollTimer();
                }, this.getScrollInterval());

            },

            pauseScrollTimer() {
                clearInterval(this._scrollTimer);
            },

            getScrollLimit() {
                return this.$refs.page.contentWindow.document.body.clientHeight - window.innerHeight;
            },

            getScrollInterval() {
                return Math.max(this.pageDuration / 10 + 1000, 5000);
            }

        },

        computed: {

            currentUrl() {

                if(this.pageIndex === null) {
                    return null;
                }

                if(this.pages[this.pageIndex] === undefined) {
                    return null;
                }

                return this.pages[this.pageIndex || 0].fields[0].value;

            },

            /**
             * Build the slideshow pages request query string.
             */
            slideshowPagesRequestQueryString() {
                return {
                    slideshow: true,
                    search: '',
                    filters: [],
                    orderBy: '',
                    orderByDirection: 'desc',
                    perPage: 100,
                    trashed: '',
                    page: 1,
                    viaResource: this.resourceName,
                    viaResourceId: this.resourceId,
                    viaRelationship: 'pages',
                    relationshipType: 'hasMany',
                }
            }

        },
    }
</script>
