<template>
    <color-swatch :primary="this.primary" :secondary="this.secondary" :pattern="this.pattern"></color-swatch>
</template>

<script>
export default {
    props: ['resourceName', 'field'],

    computed: {

        primary() {
            return JSON.parse(this.field.value).primary;
        },

        secondary() {
            return JSON.parse(this.field.value).secondary;
        },

        pattern() {
            return JSON.parse(this.field.value).pattern;
        },

    }

}
</script>
