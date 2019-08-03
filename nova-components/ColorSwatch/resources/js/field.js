Nova.booting((Vue, router, store) => {
    Vue.component('index-color-swatch', require('./components/IndexField'))
    Vue.component('detail-color-swatch', require('./components/DetailField'))
    Vue.component('form-color-swatch', require('./components/FormField'))
    Vue.component('color-swatch', require('./components/Swatch'))
})
