<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: { type: String, required: true },
    active: { type: Boolean, default: false },
});
</script>

<template>
    <Link
        :href="href"
        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
        :class="
            active
                ? 'bg-brand-teal/15 text-brand-navy'
                : 'text-gray-600 hover:bg-gray-100 hover:text-brand-navy'
        "
    >
        <span
            class="flex h-5 w-5 shrink-0 items-center justify-center"
            :class="active ? 'text-brand-teal-dark' : 'text-gray-400'"
        >
            <slot name="icon" />
        </span>
        <span class="truncate"><slot /></span>
    </Link>
</template>
