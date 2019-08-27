<template>
    <div
        class="px-2 leading-normal text-xs inline-block"
        :style="Object.assign({backgroundColor: field.background}, field.style)"
    >
        <a
            v-if="field.link"
            :href="field.link"
            :title="field.link"
            class="cursor-pointer no-underline hover:underline"
            target="_blank"
            :style="{color: field.foreground}"
        >
            <span class="block truncate">
                {{ field.label || field.value }}
            </span>
        </a>
        <router-link
            v-else-if="field.to"
            :to="field.to"
            class="cursor-pointer text-primary no-underline hover:underline"
            :style="{color: field.foreground}"
        >
            <span class="block truncate">
                {{ field.label || field.value }}
            </span>
        </router-link>
        <span v-else-if="field.value" class="block truncate" :style="{color: field.foreground}">{{ field.value }}</span>
        <span v-else>&mdash;</span>
    </div>
</template>

<script>
export default {
    props: ['field'],
}
</script>
