<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    column: { type: String, required: true },
    sort: { type: String, default: '' },
    direction: { type: String, default: 'desc' },
    align: { type: String, default: 'left' },
});

const emit = defineEmits(['sort']);

const active = computed(() => props.sort === props.column);
</script>

<template>
    <th>
        <button
            type="button"
            class="inline-flex items-center gap-1 uppercase tracking-wider"
            :class="align === 'right' ? 'ml-auto' : ''"
            @click="emit('sort', column)"
        >
            <span>{{ label }}</span>
            <svg
                class="h-3.5 w-3.5 transition-opacity"
                :class="active ? 'opacity-100 text-brand-navy' : 'opacity-30'"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    v-if="active && direction === 'asc'"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M5 15l7-7 7 7"
                />
                <path
                    v-else
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M19 9l-7 7-7-7"
                />
            </svg>
        </button>
    </th>
</template>
