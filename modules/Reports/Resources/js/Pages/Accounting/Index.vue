<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
defineProps({ stats: { type: Object, default: () => ({}) } });
</script>
<template>
    <Head title="Reports — Accounting" />
    <AdminLayout title="Reports — Accounting">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="(value, key) in stats" :key="key" class="admin-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ String(key).replaceAll('_', ' ') }}</p>
                <p class="mt-1 text-2xl font-semibold text-brand-navy">{{ value }}</p>
            </div>
        </div>
    </AdminLayout>
</template>