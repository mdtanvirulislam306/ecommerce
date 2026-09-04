<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    summary: { type: Object, required: true },
    by_stage: { type: Object, default: () => ({}) },
    by_source: { type: Array, default: () => [] },
    recent_activities: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="CRM Reports" />
    <AdminLayout title="CRM Reports">
        <div class="mb-4">
            <Link :href="route('crm.overview')" class="text-sm text-brand-navy hover:text-brand-orange">← Overview</Link>
        </div>
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="admin-card"><div class="text-xs uppercase text-gray-500">Leads</div><div class="mt-1 text-2xl font-semibold text-brand-navy">{{ summary.leads }}</div></div>
            <div class="admin-card"><div class="text-xs uppercase text-gray-500">Customers</div><div class="mt-1 text-2xl font-semibold text-brand-navy">{{ summary.active_customers }}</div></div>
            <div class="admin-card"><div class="text-xs uppercase text-gray-500">Open activities</div><div class="mt-1 text-2xl font-semibold text-brand-navy">{{ summary.open_activities }}</div></div>
        </div>
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="admin-card">
                <h2 class="mb-3 text-sm font-semibold text-brand-navy">Leads by stage</h2>
                <table class="min-w-full text-sm">
                    <tbody>
                        <tr v-for="(total, stage) in by_stage" :key="stage" class="border-t border-gray-50">
                            <td class="py-2 capitalize">{{ stage }}</td>
                            <td class="py-2 text-right">{{ total }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>
            <section class="admin-card">
                <h2 class="mb-3 text-sm font-semibold text-brand-navy">Leads by source</h2>
                <table class="min-w-full text-sm">
                    <tbody>
                        <tr v-for="row in by_source" :key="row.source" class="border-t border-gray-50">
                            <td class="py-2">{{ row.source }}</td>
                            <td class="py-2 text-right">{{ row.total }}</td>
                        </tr>
                        <tr v-if="!by_source.length"><td colspan="2" class="py-8 text-center text-gray-500">No source data.</td></tr>
                    </tbody>
                </table>
            </section>
            <section class="admin-card lg:col-span-2">
                <h2 class="mb-3 text-sm font-semibold text-brand-navy">Recent activities</h2>
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Subject</th><th class="pb-2">Type</th><th class="pb-2">Related</th></tr></thead>
                    <tbody>
                        <tr v-for="row in recent_activities" :key="row.id" class="border-t border-gray-50">
                            <td class="py-2">{{ row.subject }}</td>
                            <td class="py-2 capitalize">{{ row.type }}</td>
                            <td class="py-2">{{ row.related || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </AdminLayout>
</template>
