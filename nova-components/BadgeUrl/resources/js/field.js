Nova.booting((Vue, router, store) => {
    Vue.component('index-badge-url', require('./components/IndexField'))
    Vue.component('detail-badge-url', require('./components/DetailField'))
    Vue.component('form-badge-url', require('./components/FormField'))
    Vue.component('badge-url', require('./components/BadgeUrl'))
})
