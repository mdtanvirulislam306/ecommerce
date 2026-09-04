<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    journal: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);
</script>

<template>
    <Head :title="journal.number" />

    <AdminLayout :title="journal.number">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4">
            <Link :href="route('accounting.journal-entries.index')" class="text-sm text-brand-navy hover:text-brand-orange">
                ← Journals
            </Link>
        </div>

        <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
            <section class="admin-card space-y-3 text-sm">
                <div>
                    <p class="text-xs text-gray-500">Status</p>
                    <p class="font-medium text-emerald-700">{{ journal.status_label }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Date</p>
                    <p>{{ journal.entry_date }}</p>
                </div>
                <div v-if="journal.memo">
                    <p class="text-xs text-gray-500">Memo</p>
                    <p>{{ journal.memo }}</p>
                </div>
                <div v-if="journal.source_type">
                    <p class="text-xs text-gray-500">Source</p>
                    <p class="font-mono text-xs">{{ journal.source_type }} #{{ journal.source_id }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Posted</p>
                    <p>{{ formatDateTime(journal.posted_at || journal.created_at) }}</p>
                </div>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs text-gray-500">Totals</p>
                    <p class="font-semibold text-brand-navy">
                        Dr {{ Number(journal.total_debit).toFixed(2) }} / Cr {{ Number(journal.total_credit).toFixed(2) }}
                    </p>
                </div>
            </section>

            <section class="admin-card">
                <h2 class="text-sm font-semibold text-brand-navy">Lines</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs text-gray-500">
                            <tr>
                                <th class="pb-2">Account</th>
                                <th class="pb-2">Memo</th>
                                <th class="pb-2 text-right">Debit</th>
                                <th class="pb-2 text-right">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="line in journal.lines" :key="line.id" class="border-t border-gray-50">
                                <td class="py-2">
                                    <span class="font-mono text-xs text-gray-500">{{ line.account_code }}</span>
                                    <div class="font-medium text-brand-navy">{{ line.account_name }}</div>
                                </td>
                                <td class="py-2 text-gray-500">{{ line.memo || '—' }}</td>
                                <td class="py-2 text-right">{{ Number(line.debit) > 0 ? Number(line.debit).toFixed(2) : '' }}</td>
                                <td class="py-2 text-right">{{ Number(line.credit) > 0 ? Number(line.credit).toFixed(2) : '' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
