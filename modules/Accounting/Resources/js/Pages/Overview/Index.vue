<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
    recentJournals: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Accounting Overview" />

    <AdminLayout title="Accounting Overview">
        <p class="mb-5 text-sm text-gray-500">
            Double-entry books. Sales confirm posts Dr AR / Cr Revenue; purchase receive posts Dr Inventory / Cr AP.
        </p>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Active accounts</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.accounts }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Posted journals</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.journals }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">This month</p>
                <p class="text-2xl font-semibold text-sky-700">{{ stats.posted_month }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Drafts</p>
                <p class="text-2xl font-semibold text-gray-600">{{ stats.draft }}</p>
            </div>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <Link
                :href="route('accounting.journal-entries.create')"
                class="rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white"
            >
                Manual journal
            </Link>
            <Link
                :href="route('accounting.accounts.tree')"
                class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-brand-navy"
            >
                Chart of accounts
            </Link>
            <Link
                :href="route('accounting.reports.trial-balance')"
                class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-brand-navy"
            >
                Trial balance
            </Link>
        </div>

        <section class="admin-card">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-brand-navy">Recent journals</h2>
                <Link :href="route('accounting.journal-entries.index')" class="text-xs text-brand-orange hover:underline">
                    View all →
                </Link>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs text-gray-500">
                        <tr>
                            <th class="pb-2">Number</th>
                            <th class="pb-2">Date</th>
                            <th class="pb-2">Memo</th>
                            <th class="pb-2">Lines</th>
                            <th class="pb-2">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="journal in recentJournals" :key="journal.id" class="border-t border-gray-50">
                            <td class="py-2">
                                <Link
                                    :href="route('accounting.journal-entries.show', journal.id)"
                                    class="font-medium text-brand-navy hover:text-brand-orange"
                                >
                                    {{ journal.number }}
                                </Link>
                            </td>
                            <td class="py-2 text-gray-600">{{ journal.entry_date }}</td>
                            <td class="py-2 text-gray-600">{{ journal.memo || '—' }}</td>
                            <td class="py-2">{{ journal.lines_count }}</td>
                            <td class="py-2 text-gray-500">{{ formatDateTime(journal.created_at) }}</td>
                        </tr>
                        <tr v-if="!recentJournals.length">
                            <td colspan="5" class="py-8 text-center text-gray-500">No journals yet. Confirm a sale or receive a PO.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AdminLayout>
</template>
