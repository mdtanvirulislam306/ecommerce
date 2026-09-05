<script setup>
import AdminDateRangePicker from '@/Components/Admin/AdminDateRangePicker.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    summary: { type: Object, required: true },
    funnel: { type: Array, default: () => [] },
    by_stage: { type: Object, default: () => ({}) },
    by_source: { type: Array, default: () => [] },
    recent_activities: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

const topReached = computed(() => Math.max(1, ...(props.funnel.map((row) => row.reached) || [1])));
const maxSource = computed(() => Math.max(1, ...(props.by_source.map((row) => row.total) || [1])));

const stageMeta = {
    new: 'bg-gray-400',
    contacted: 'bg-sky-500',
    qualified: 'bg-amber-500',
    proposal: 'bg-violet-500',
    won: 'bg-emerald-500',
    lost: 'bg-red-400',
};

const percent = (value) => (value === null || value === undefined ? '—' : `${value}%`);

const applyDateRange = ({ from, to }) => {
    dateFrom.value = from || '';
    dateTo.value = to || '';
    router.get(
        route('crm.reports'),
        {
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const clearDates = () => {
    applyDateRange({ from: '', to: '' });
};
</script>

<template>
    <Head title="CRM Reports" />

    <AdminLayout title="CRM Reports">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <Link :href="route('crm.overview')" class="text-sm font-medium text-brand-navy hover:text-brand-orange">← Overview</Link>
                <p class="mt-1 text-xs text-gray-500">
                    Funnel uses current stages for leads created in the selected period (lost shown separately).
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <AdminDateRangePicker
                    :from="dateFrom"
                    :to="dateTo"
                    placeholder="Lead created range"
                    @update="applyDateRange"
                />
                <button
                    v-if="dateFrom || dateTo"
                    type="button"
                    class="text-xs font-medium text-brand-orange hover:text-brand-orange-dark"
                    @click="clearDates"
                >
                    Reset to last 90 days
                </button>
                <Link :href="route('crm.leads.all', { view: 'pipeline' })" class="text-sm font-medium text-brand-orange hover:underline">
                    Open pipeline
                </Link>
            </div>
        </div>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Leads created</p>
                <p class="mt-2 text-2xl font-semibold text-brand-navy">{{ summary.leads }}</p>
            </div>
            <div class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Open pipeline</p>
                <p class="mt-2 text-2xl font-semibold text-sky-700">{{ summary.open_pipeline }}</p>
            </div>
            <div class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Won</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ summary.won }}</p>
            </div>
            <div class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Lost</p>
                <p class="mt-2 text-2xl font-semibold text-red-600">{{ summary.lost }}</p>
            </div>
            <div class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Win rate</p>
                <p class="mt-2 text-2xl font-semibold text-brand-navy">{{ percent(summary.win_rate) }}</p>
                <p class="mt-1 text-xs text-gray-500">Won ÷ (won + lost)</p>
            </div>
            <div class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Customer convert</p>
                <p class="mt-2 text-2xl font-semibold text-brand-navy">{{ percent(summary.convert_rate) }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ summary.converted }} converted</p>
            </div>
        </div>

        <section class="admin-card mb-6">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Conversion funnel</h2>
                    <p class="mt-0.5 text-xs text-gray-500">
                        “Reached” = currently at this stage or further. Step % = reached here ÷ reached previous step.
                    </p>
                </div>
            </div>

            <div v-if="funnel.length" class="space-y-4">
                <div v-for="(row, index) in funnel" :key="row.stage">
                    <div class="mb-1.5 flex flex-wrap items-center justify-between gap-2 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-brand-navy">{{ row.label }}</span>
                            <span class="text-xs text-gray-500">{{ row.in_stage }} in stage · {{ row.reached }} reached</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span v-if="index > 0" class="font-medium text-gray-600">
                                Step {{ percent(row.conversion_from_previous) }}
                            </span>
                            <span class="font-semibold text-brand-navy">{{ percent(row.share_of_top) }} of top</span>
                        </div>
                    </div>
                    <div class="h-3 overflow-hidden rounded-full bg-gray-100">
                        <div
                            class="h-full rounded-full transition-all"
                            :class="stageMeta[row.stage] || 'bg-brand-teal'"
                            :style="{ width: `${(row.reached / topReached) * 100}%` }"
                        />
                    </div>
                </div>
            </div>
            <p v-else class="py-10 text-center text-sm text-gray-500">No funnel data for this period.</p>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="admin-card">
                <h2 class="mb-4 text-sm font-semibold text-brand-navy">Leads by stage</h2>
                <div class="space-y-3">
                    <div v-for="(total, stage) in by_stage" :key="stage">
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="capitalize text-gray-600">{{ stage }}</span>
                            <span class="font-medium text-brand-navy">{{ total }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full"
                                :class="stageMeta[stage] || 'bg-brand-teal'"
                                :style="{ width: `${(total / Math.max(1, ...Object.values(by_stage))) * 100}%` }"
                            />
                        </div>
                    </div>
                    <p v-if="!Object.keys(by_stage).length" class="py-6 text-center text-sm text-gray-500">No stage data yet.</p>
                </div>
            </section>

            <section class="admin-card">
                <h2 class="mb-4 text-sm font-semibold text-brand-navy">Source performance</h2>
                <div class="space-y-3">
                    <div v-for="row in by_source" :key="row.source">
                        <div class="mb-1 flex items-center justify-between gap-2 text-sm">
                            <span class="text-gray-600">{{ row.source }}</span>
                            <span class="text-right">
                                <span class="font-medium text-brand-navy">{{ row.total }}</span>
                                <span class="ml-2 text-xs text-gray-500">
                                    {{ row.won }}W / {{ row.lost }}L · {{ percent(row.win_rate) }}
                                </span>
                            </span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full bg-brand-orange"
                                :style="{ width: `${(row.total / maxSource) * 100}%` }"
                            />
                        </div>
                    </div>
                    <p v-if="!by_source.length" class="py-6 text-center text-sm text-gray-500">No source data yet.</p>
                </div>
            </section>

            <section class="admin-card lg:col-span-2">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-brand-navy">Recent activities</h2>
                    <div class="flex gap-3 text-xs text-gray-500">
                        <span>{{ summary.open_activities }} open</span>
                        <span>{{ summary.completed_activities }} done</span>
                        <span>{{ summary.active_customers }} active customers</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="border-b border-gray-100">
                            <tr class="admin-data-table__head">
                                <th>Subject</th>
                                <th>Type</th>
                                <th>Related</th>
                                <th>Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in recent_activities" :key="row.id" class="admin-data-table__row">
                                <td class="admin-data-table__cell font-medium text-brand-navy">{{ row.subject }}</td>
                                <td class="admin-data-table__cell capitalize text-gray-600">{{ row.type }}</td>
                                <td class="admin-data-table__cell text-gray-500">{{ row.related || '—' }}</td>
                                <td class="admin-data-table__cell text-gray-500">
                                    {{ row.due_at ? formatDateTime(row.due_at) : '—' }}
                                </td>
                            </tr>
                            <tr v-if="!recent_activities.length">
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500">No recent activity.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
