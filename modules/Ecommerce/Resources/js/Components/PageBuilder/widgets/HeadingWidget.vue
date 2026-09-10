<script setup>
import { computed } from 'vue';

const props = defineProps({
    widget: { type: Object, required: true },
});

const tag = computed(() => {
    const value = props.widget.settings?.tag;
    return ['h1', 'h2', 'h3', 'h4'].includes(value) ? value : 'h2';
});

const alignClass = computed(() => ({
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
}[props.widget.settings?.align] ?? 'text-left'));

const sizeClass = computed(() => ({
    h1: 'text-4xl font-bold tracking-tight',
    h2: 'text-3xl font-semibold tracking-tight',
    h3: 'text-2xl font-semibold',
    h4: 'text-xl font-semibold',
}[tag.value]));
</script>

<template>
    <component
        :is="tag"
        class="text-brand-navy"
        :class="[alignClass, sizeClass]"
        :style="{ color: widget.settings?.color || undefined }"
    >
        {{ widget.settings?.text || 'Heading' }}
    </component>
</template>
