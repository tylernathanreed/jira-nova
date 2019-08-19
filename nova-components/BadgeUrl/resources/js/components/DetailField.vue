<template>
    <panel-item :field="field">
        <template slot="value">
            <badge-url :field="field"/>
        </template>
    </panel-item>
</template>

<script>
export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],
}
</script>
