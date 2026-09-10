<script setup>
import { computed } from 'vue';

const props = defineProps({
    widget: { type: Object, required: true },
});

const alignClass = computed(() => ({
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
}[props.widget.settings?.align] ?? 'text-left'));
</script>

<template>
    <p class="whitespace-pre-wrap text-base leading-7 text-gray-700" :class="alignClass">
        {{ widget.settings?.content || '' }}
    </p>
</template>
