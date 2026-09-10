<script setup>
import { computed } from 'vue';

const props = defineProps({
    widget: { type: Object, required: true },
});

const html = computed(() => {
    if (props.widget.data && Object.prototype.hasOwnProperty.call(props.widget.data, 'html')) {
        return props.widget.data.html;
    }

    return props.widget.settings?.content || '';
});
</script>

<template>
    <div v-if="html" class="prose max-w-none text-gray-700" v-html="html" />
    <p v-else class="text-sm text-gray-400">HTML content</p>
</template>
