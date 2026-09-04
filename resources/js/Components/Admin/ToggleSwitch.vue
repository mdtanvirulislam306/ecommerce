<script setup>
const props = defineProps({
    modelValue: { type: Boolean, default: false },
    label: { type: String, default: '' },
    description: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const toggle = () => emit('update:modelValue', !props.modelValue);
</script>

<template>
    <div class="flex items-center justify-between gap-4">
        <div class="min-w-0">
            <p v-if="label" class="text-sm font-medium text-brand-navy">{{ label }}</p>
            <p v-if="description" class="mt-0.5 text-xs text-gray-500">{{ description }}</p>
        </div>
        <button
            type="button"
            role="switch"
            :aria-checked="modelValue"
            class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-brand-teal/40 focus:ring-offset-2"
            :class="modelValue ? 'bg-brand-teal' : 'bg-gray-200'"
            @click="toggle"
        >
            <span
                class="inline-block h-5 w-5 transform rounded-full bg-white shadow-sm transition-transform duration-200"
                :class="modelValue ? 'translate-x-6' : 'translate-x-1'"
            />
        </button>
    </div>
</template>
