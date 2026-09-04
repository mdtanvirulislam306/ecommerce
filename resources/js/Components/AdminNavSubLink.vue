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
        class="block rounded-md px-3 py-2 text-sm transition-colors"
        :class="
            active
                ? 'bg-brand-teal/15 font-medium text-brand-navy'
                : 'text-gray-600 hover:bg-gray-100 hover:text-brand-navy'
        "
    >
        <slot />
    </Link>
</template>
