<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    module: { type: String, required: true },
    page: { type: String, default: 'overview' },
    sub: { type: String, default: null },
    title: { type: String, required: true },
});
</script>

<template>
    <Head :title="title" />

    <AdminLayout :title="title">
        <div class="admin-card">
            <p class="text-sm text-gray-500">
                {{ module }} / {{ page }}{{ sub ? ` / ${sub}` : '' }}
            </p>
            <h2 class="mt-2 text-lg font-semibold text-brand-navy">{{ title }}</h2>
            <p class="mt-2 text-sm text-gray-600">
                Module shell — feature implementation comes next.
            </p>
        </div>
    </AdminLayout>
</template>
