<template>
    <base-trend-metric
        @selected="handleRangeSelected"
        :title="card.name"
        :value="value"
        :chart-data="data"
        :ranges="card.ranges"
        :format="format"
        :prefix="prefix"
        :suffix="suffix"
        :selected-range-key="selectedRangeKey"
        :loading="loading"
    />
</template>

<script>
import _ from 'lodash'
import { InteractsWithDates, Minimum } from 'laravel-nova'

export default {
    mixins: [InteractsWithDates],

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
        value: '',
        data: [],
        format: '(0[.]00a)',
        prefix: '',
        suffix: '',
        selectedRangeKey: null,
    }),

    watch: {
        resourceId() {
            this.fetch()
        },
    },

    created() {

        if (this.hasRanges) {
            this.selectedRangeKey = this.card.ranges[0].value
        }

    },

    mounted() {

        this.fetch();

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
        handleRangeSelected(key) {
            this.selectedRangeKey = key
            this.fetch()
        },

        fetch() {

            this.loading = true;

            if(!this.getResourceProvider().isLoaded()) {
                return;
            }

            Minimum(Nova.request().post(this.metricEndpoint, this.getMetricPayload())).then(
                ({
                    data: {
                        value: { labels, trend, value, prefix, suffix, format },
                    },
                }) => {
                    this.value = value
                    this.labels = Object.keys(trend)
                    this.data = {
                        labels: Object.keys(trend),
                        series: [
                            _.map(trend, (value, label) => {
                                return {
                                    meta: label,
                                    value: value,
                                }
                            }),
                        ],
                    }
                    this.format = format || this.format
                    this.prefix = prefix || this.prefix
                    this.suffix = suffix || this.suffix
                    this.loading = false
                }
            )
        },

        getMetricPayload() {

            return Object.assign(this.metricPayload.params, {
                'resourceData': JSON.stringify(this.getResourceData())
            });

        },

        getResourceData() {
            return this.getResourceProvider().getResourceData();
        }
    },

    computed: {
        hasRanges() {
            return this.card.ranges.length > 0
        },

        metricPayload() {
            const payload = {
                params: {
                    timezone: this.userTimezone,
                    twelveHourTime: this.usesTwelveHourTime
                },
            }

            if (this.hasRanges) {
                payload.params.range = this.selectedRangeKey
            }

            return payload
        },

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
