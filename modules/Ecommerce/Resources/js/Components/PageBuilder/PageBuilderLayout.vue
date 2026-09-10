<script setup>
defineProps({
    title: { type: String, default: 'Page Builder' },
});
</script>

<template>
    <div class="flex h-screen flex-col bg-slate-100 text-brand-navy">
        <header class="flex h-14 shrink-0 items-center gap-3 border-b border-gray-200 bg-white px-3 sm:px-4">
            <slot name="header" />
        </header>
        <div class="flex min-h-0 flex-1">
            <slot />
        </div>
    </div>
</template>
