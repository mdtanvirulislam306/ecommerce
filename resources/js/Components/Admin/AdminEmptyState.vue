<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    actionLabel: { type: String, default: '' },
});

const emit = defineEmits(['action']);
</script>

<template>
    <div class="admin-card px-6 py-14 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-teal/10 text-brand-teal-dark">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
            </svg>
        </div>
        <h3 class="text-base font-semibold text-brand-navy">{{ title }}</h3>
        <p v-if="description" class="mx-auto mt-1.5 max-w-md text-sm text-gray-500">
            {{ description }}
        </p>
        <button
            v-if="actionLabel"
            type="button"
            class="mt-5 inline-flex items-center gap-1.5 rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-orange-dark"
            @click="emit('action')"
        >
            {{ actionLabel }}
        </button>
        <slot />
    </div>
</template>
