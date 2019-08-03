<template>
    <panel-item :field="field">
    	<template slot="value">
    		<color-swatch :primary="this.primary" :secondary="this.secondary" :pattern="this.pattern"></color-swatch>
    	</template>
    </panel-item>
</template>

<script>
export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],

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
