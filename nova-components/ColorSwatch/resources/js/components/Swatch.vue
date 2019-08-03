<template>
    <div :style="swatchStyle"></div>
</template>

<script>
export default {
    props: ['primary', 'secondary', 'pattern'],

    methods: {

        refresh() {

            _.each(this.swatchStyle, (v, k) => this.$el.style[k] = v);

        }

    },

    computed: {

        baseStyle() {

            return {
                'width': '1.5rem',
                'height': '1.5rem',
                'border': '1px solid black',
                'box-shadow': 'inset 0 0 1px rgba(0, 0, 0, 0.5), inset 0 0 2px rgba(0, 0, 0, 0.5)'
            };

        },

        backgroundStyle() {

            return {
                'background': `linear-gradient(
                    135deg,
                    ${this.primary} 33.33%,
                    ${this.secondary} 33.33%,
                    ${this.secondary} 50%,
                    ${this.primary} 50%,
                    ${this.primary} 83.33%,
                    ${this.secondary} 83.33%,
                    ${this.secondary} 100%
                )`.replace(/\r?\n\s*/g, ''),
                'background-size': '4.24px 4.24px',
            };

        },

        swatchStyle() {
            return Object.assign(this.baseStyle, this.backgroundStyle);
        }

    }

}
</script>
