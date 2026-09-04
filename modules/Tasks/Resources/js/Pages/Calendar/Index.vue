<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
defineProps({ items: { type: Array, default: () => [] } });
</script>
<template>
    <Head title="Task Calendar" />
    <AdminLayout title="Task Calendar">
        <section class="admin-card">
            <ul class="divide-y divide-gray-100 text-sm">
                <li v-for="row in items" :key="row.id" class="flex justify-between py-2">
                    <span class="font-medium text-brand-navy">{{ row.title }}</span>
                    <span class="text-gray-500">{{ row.due_at }} · {{ row.status }}</span>
                </li>
                <li v-if="!items.length" class="py-6 text-center text-gray-500">No dated tasks.</li>
            </ul>
        </section>
    </AdminLayout>
</template>