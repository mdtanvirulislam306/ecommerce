<script setup>
import { computed } from 'vue';

const props = defineProps({
    widget: { type: Object, required: true },
    editing: { type: Boolean, default: false },
});

const alignClass = computed(() => ({
    left: 'justify-start',
    center: 'justify-center',
    right: 'justify-end',
}[props.widget.settings?.align] ?? 'justify-start'));

const styleClass = computed(() => (
    props.widget.settings?.style === 'secondary'
        ? 'border border-brand-navy bg-white text-brand-navy hover:bg-gray-50'
        : 'bg-brand-orange text-white hover:bg-brand-orange-dark'
));
</script>

<template>
    <div class="flex" :class="alignClass">
        <a
            :href="editing ? undefined : (widget.settings?.url || '#')"
            :class="styleClass"
            class="inline-flex items-center rounded-lg px-5 py-2.5 text-sm font-semibold transition"
            @click="editing ? $event.preventDefault() : null"
        >
            {{ widget.settings?.label || 'Button' }}
        </a>
    </div>
</template>
