<script setup>
const model = defineModel({ type: Boolean, default: false });

defineProps({
    label: { type: String, default: null },
    disabled: { type: Boolean, default: false },
});
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="model"
        :aria-label="label"
        :disabled="disabled"
        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-teal focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
        :class="model ? 'bg-brand-teal' : 'bg-gray-300'"
        @click="model = !model"
    >
        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition" :class="model ? 'translate-x-5' : 'translate-x-0.5'" />
    </button>
</template>
