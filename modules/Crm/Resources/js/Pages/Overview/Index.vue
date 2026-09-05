<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
    recentLeads: { type: Array, default: () => [] },
});

const stageMeta = {
    new: { class: 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' },
    contacted: { class: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200' },
    qualified: { class: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' },
    proposal: { class: 'bg-violet-50 text-violet-700 ring-1 ring-violet-200' },
    won: { class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    lost: { class: 'bg-red-50 text-red-700 ring-1 ring-red-200' },
};
</script>

<template>
    <Head title="CRM Overview" />

    <AdminLayout title="CRM Overview">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500">
                    Move leads New → Contacted → Qualified → Proposal → Won, then convert them into customers.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Link :href="route('crm.leads.create')" class="inline-flex items-center rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark">
                    Add lead
                </Link>
                <Link :href="route('crm.leads.all', { view: 'pipeline' })" class="inline-flex items-center rounded-lg bg-brand-navy px-4 py-2 text-sm font-medium text-white hover:bg-brand-navy-dark">
                    Pipeline
                </Link>
            </div>
        </div>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <Link :href="route('crm.customers.all')" class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Customers</p>
                <p class="mt-2 text-2xl font-semibold text-brand-navy">{{ stats.customers }}</p>
            </Link>
            <Link :href="route('crm.leads.all')" class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">All leads</p>
                <p class="mt-2 text-2xl font-semibold text-brand-navy">{{ stats.leads }}</p>
            </Link>
            <Link :href="route('crm.leads.all', { stage: 'new' })" class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">New</p>
                <p class="mt-2 text-2xl font-semibold text-gray-700">{{ stats.new }}</p>
            </Link>
            <Link :href="route('crm.leads.all', { view: 'pipeline' })" class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">In pipeline</p>
                <p class="mt-2 text-2xl font-semibold text-sky-700">{{ stats.pipeline }}</p>
            </Link>
            <Link :href="route('crm.leads.all', { stage: 'won' })" class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Won</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ stats.won }}</p>
            </Link>
            <Link :href="route('crm.activities.follow-ups')" class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Due follow-ups</p>
                <p class="mt-2 text-2xl font-semibold text-brand-orange">{{ stats.activities_due }}</p>
            </Link>
        </div>

        <section class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Recent leads</h2>
                    <p class="text-xs text-gray-500">Latest prospects added to the pipeline</p>
                </div>
                <Link :href="route('crm.leads.all')" class="text-sm font-medium text-brand-orange hover:text-brand-orange-dark">
                    View all →
                </Link>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Name</th>
                            <th>Company</th>
                            <th>Stage</th>
                            <th>Next action</th>
                            <th>Source</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="lead in recentLeads" :key="lead.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ lead.name }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ lead.company || '—' }}</td>
                            <td class="admin-data-table__cell">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" :class="stageMeta[lead.stage]?.class">
                                    {{ lead.stage_label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-sm">
                                <template v-if="lead.next_action">
                                    <p :class="lead.next_action.is_overdue ? 'font-medium text-red-600' : 'text-brand-navy'">
                                        {{ lead.next_action.subject }}
                                    </p>
                                    <p class="text-xs" :class="lead.next_action.is_overdue ? 'text-red-500' : 'text-gray-500'">
                                        {{ formatDateTime(lead.next_action.due_at) }}
                                    </p>
                                </template>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="admin-data-table__cell text-gray-500">{{ lead.source || '—' }}</td>
                            <td class="admin-data-table__cell text-gray-500">{{ formatDateTime(lead.created_at) }}</td>
                        </tr>
                        <tr v-if="!recentLeads.length">
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">
                                No leads yet.
                                <Link :href="route('crm.leads.create')" class="ml-1 font-medium text-brand-orange hover:underline">Add one</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AdminLayout>
</template>
