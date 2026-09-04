<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    rows: { type: Array, default: () => [] },
    totals: { type: Object, required: true },
});
</script>

<template>
    <Head title="Trial Balance" />

    <AdminLayout title="Trial Balance">
        <div class="mb-4">
            <Link :href="route('accounting.overview')" class="text-sm text-brand-navy hover:text-brand-orange">← Overview</Link>
        </div>

        <section class="admin-card">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs text-gray-500">
                        <tr>
                            <th class="pb-2">Code</th>
                            <th class="pb-2">Account</th>
                            <th class="pb-2">Type</th>
                            <th class="pb-2 text-right">Debit</th>
                            <th class="pb-2 text-right">Credit</th>
                            <th class="pb-2 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.account_id" class="border-t border-gray-50">
                            <td class="py-2 font-mono text-xs">{{ row.code }}</td>
                            <td class="py-2 font-medium text-brand-navy">{{ row.name }}</td>
                            <td class="py-2 capitalize text-gray-500">{{ row.type }}</td>
                            <td class="py-2 text-right">{{ Number(row.debit).toFixed(2) }}</td>
                            <td class="py-2 text-right">{{ Number(row.credit).toFixed(2) }}</td>
                            <td class="py-2 text-right font-medium">{{ Number(row.balance).toFixed(2) }}</td>
                        </tr>
                        <tr v-if="!rows.length">
                            <td colspan="6" class="py-10 text-center text-gray-500">No posted activity yet.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length" class="border-t border-gray-200 font-semibold text-brand-navy">
                        <tr>
                            <td class="pt-3" colspan="3">Totals</td>
                            <td class="pt-3 text-right">{{ Number(totals.debit).toFixed(2) }}</td>
                            <td class="pt-3 text-right">{{ Number(totals.credit).toFixed(2) }}</td>
                            <td class="pt-3 text-right">—</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </AdminLayout>
</template>
