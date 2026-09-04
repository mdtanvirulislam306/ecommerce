<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
    recentLeads: { type: Array, default: () => [] },
});

const stageMeta = {
    new: { class: 'bg-gray-100 text-gray-600' },
    contacted: { class: 'bg-sky-50 text-sky-700' },
    qualified: { class: 'bg-amber-50 text-amber-800' },
    proposal: { class: 'bg-violet-50 text-violet-700' },
    won: { class: 'bg-emerald-50 text-emerald-700' },
    lost: { class: 'bg-red-50 text-red-700' },
};
</script>

<template>
    <Head title="CRM Overview" />

    <AdminLayout title="CRM Overview">
        <p class="mb-5 text-sm text-gray-500">
            Leads flow New → Contacted → Qualified → Proposal → Won. Convert won/qualified leads into customers (Commerce groups for pricing).
        </p>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Active customers</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.customers }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">All leads</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.leads }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">New</p>
                <p class="text-2xl font-semibold text-gray-700">{{ stats.new }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">In pipeline</p>
                <p class="text-2xl font-semibold text-sky-700">{{ stats.pipeline }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Won</p>
                <p class="text-2xl font-semibold text-emerald-700">{{ stats.won }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Due follow-ups</p>
                <p class="text-2xl font-semibold text-brand-orange">{{ stats.activities_due }}</p>
            </div>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <Link
                :href="route('crm.leads.pipeline')"
                class="rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white"
            >
                Lead pipeline
            </Link>
            <Link
                :href="route('crm.customers.all')"
                class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-brand-navy"
            >
                Customers
            </Link>
            <Link
                :href="route('crm.activities.follow-ups')"
                class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-brand-navy"
            >
                Follow-ups
            </Link>
        </div>

        <section class="admin-card">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-brand-navy">Recent leads</h2>
                <Link :href="route('crm.leads.all')" class="text-xs text-brand-orange hover:underline">View all →</Link>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs text-gray-500">
                        <tr>
                            <th class="pb-2">Name</th>
                            <th class="pb-2">Company</th>
                            <th class="pb-2">Stage</th>
                            <th class="pb-2">Source</th>
                            <th class="pb-2">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="lead in recentLeads" :key="lead.id" class="border-t border-gray-50">
                            <td class="py-2 font-medium text-brand-navy">{{ lead.name }}</td>
                            <td class="py-2 text-gray-600">{{ lead.company || '—' }}</td>
                            <td class="py-2">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="stageMeta[lead.stage]?.class">
                                    {{ lead.stage_label }}
                                </span>
                            </td>
                            <td class="py-2 text-gray-500">{{ lead.source || '—' }}</td>
                            <td class="py-2 text-gray-500">{{ formatDateTime(lead.created_at) }}</td>
                        </tr>
                        <tr v-if="!recentLeads.length">
                            <td colspan="5" class="py-8 text-center text-gray-500">No leads yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AdminLayout>
</template>
