<script setup>
import { computed } from 'vue';

const props = defineProps({
    widget: { type: Object, required: true },
});

const widthClass = computed(() => (
    props.widget.settings?.width === 'contained' ? 'max-w-xl mx-auto' : 'w-full'
));
</script>

<template>
    <div :class="widthClass">
        <img
            v-if="widget.settings?.url"
            :src="widget.settings.url"
            :alt="widget.settings?.alt || ''"
            class="h-auto w-full rounded-xl object-cover"
        />
        <div
            v-else
            class="flex h-40 items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 text-sm text-gray-400"
        >
            Select an image
        </div>
    </div>
</template>
