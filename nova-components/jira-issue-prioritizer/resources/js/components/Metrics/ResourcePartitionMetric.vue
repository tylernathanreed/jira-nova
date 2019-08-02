<template>
    <base-partition-metric :title="card.name" :chart-data="chartData" :loading="loading" />
</template>

<script>
import { Minimum } from 'laravel-nova'

export default {
    props: {
        card: {
            type: Object,
            required: true,
        },

        resourceName: {
            type: String,
            default: '',
        },

        resourceId: {
            type: [Number, String],
            default: '',
        },

        lens: {
            type: String,
            default: '',
        },
    },

    data: () => ({
        loading: true,
        chartData: [],
    }),

    watch: {
        resourceId() {
            this.fetch()
        },
    },

    created() {
        this.fetch()
    },

    mounted() {

        Nova.$on('resources-loading', () => {
            this.loading = true;
        });

        Nova.$on('resources-loaded', () => {

            this.$nextTick(() => {
                this.fetch();
            });

        });

    },

    methods: {

        fetch() {

            this.loading = true;

            if(!this.getResourceProvider().isLoaded()) {
                return;
            }

            Minimum(Nova.request().post(this.metricEndpoint, this.getMetricPayload())).then(
                ({
                    data: {
                        value: { value }
                    }
                }) => {
                    this.chartData = value
                    this.loading = false
                }
            );

        },

        getMetricPayload() {

            return {
                'resourceData': JSON.stringify(this.getResourceData())
            };

        },

        getResourceData() {
            return this.getResourceProvider().getResourceData();
        }

    },

    computed: {
        metricEndpoint() {
            const lens = this.lens !== '' ? `/lens/${this.lens}` : ''
            if (this.resourceName && this.resourceId) {
                return `/nova-api/${this.resourceName}${lens}/${this.resourceId}/metrics/${this.card.uriKey}`
            } else if (this.resourceName) {
                return `/nova-api/${this.resourceName}${lens}/metrics/${this.card.uriKey}`
            } else {
                return `/nova-api/metrics/${this.card.uriKey}`
            }
        },
    },

    inject: [
        'getResourceProvider'
    ]
}
</script>
