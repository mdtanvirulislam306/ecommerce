<script setup>
import { computed } from 'vue';

const props = defineProps({
    widget: { type: Object, required: true },
});

const height = computed(() => {
    const value = Number(props.widget.settings?.height);
    return Number.isFinite(value) ? Math.min(240, Math.max(8, value)) : 32;
});
</script>

<template>
    <div :style="{ height: `${height}px` }" />
</template>
