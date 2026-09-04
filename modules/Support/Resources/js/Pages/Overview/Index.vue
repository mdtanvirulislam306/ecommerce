<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
    recent: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Support Overview" />

    <AdminLayout title="Support Overview">
        <p class="mb-5 text-sm text-gray-500">Helpdesk ticket scaffold — categories and canned replies come later.</p>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Open</p>
                <p class="text-2xl font-semibold text-brand-orange">{{ stats.open }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">In progress</p>
                <p class="text-2xl font-semibold text-sky-700">{{ stats.in_progress }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Resolved</p>
                <p class="text-2xl font-semibold text-emerald-700">{{ stats.resolved }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Total</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.total }}</p>
            </div>
        </div>

        <div class="mb-4">
            <Link :href="route('support.tickets.index')" class="rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white">
                View tickets
            </Link>
        </div>

        <section class="admin-card">
            <h2 class="text-sm font-semibold text-brand-navy">Recent tickets</h2>
            <ul class="mt-3 divide-y divide-gray-100 text-sm">
                <li v-for="row in recent" :key="row.id" class="flex justify-between gap-3 py-2">
                    <span class="font-medium text-brand-navy">{{ row.number }} — {{ row.subject }}</span>
                    <span class="text-gray-500">{{ row.status_label }}</span>
                </li>
                <li v-if="!recent.length" class="py-6 text-center text-gray-500">No tickets yet.</li>
            </ul>
        </section>
    </AdminLayout>
</template>
