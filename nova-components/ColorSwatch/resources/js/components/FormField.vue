<template>
    <default-field :field="field" :errors="errors">
        <template slot="field">
            <div class="flex items-center">
                <color-swatch ref="swatch" class="mr-8" :primary="primary" :secondary="secondary" :pattern="pattern"></color-swatch>

                <input type="color" ref="primary" class="form-control w-9 mr-2" v-model="primary" @change="update">
                <input type="color" ref="secondary" class="form-control w-9" v-model="secondary" @change="update">
            </div>
        </template>
    </default-field>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    data: () => ({
        primary: '#000000',
        secondary: '#000000',
        pattern: 'thin-diagonal-stripe',
    }),

    methods: {
        /*
         * Set the initial, internal value for the field.
         */
        setInitialValue() {

            let value = {};

            try {
                value = JSON.parse(this.field.value) || {};
            } catch(e) {
                value = {};
            }

            this.value = value;

            this.primary = value.primary || '#000000';
            this.secondary = value.secondary || '#000000';
            this.pattern = value.pattern || 'thin-diagonal-stripe';

            this.$nextTick(() => {
                this.update();
            });

        },

        /**
         * Fill the given FormData object with the field's internal value.
         */
        fill(formData) {
            formData.append(this.field.attribute, JSON.stringify({
                'primary': this.primary,
                'secondary': this.secondary,
                'pattern': this.pattern
            }))
        },

        /**
         * Update the field's internal value.
         */
        handleChange(value) {
            this.value = value
        },

        update() {
            this.$refs.swatch.refresh()
        }

    },
}
</script>
