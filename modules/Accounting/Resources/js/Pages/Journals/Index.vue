<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    journals: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);

const visitIndex = () => {
    router.get(
        route('accounting.journal-entries.index'),
        { search: search.value || undefined, per_page: perPage.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
</script>

<template>
    <Head title="Journal Entries" />

    <AdminLayout title="Journal Entries">
        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <h2 class="text-sm font-semibold text-brand-navy">Journal entries</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <Link :href="route('accounting.journal-entries.create')">
                        <PrimaryButton type="button">New journal</PrimaryButton>
                    </Link>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Number</th>
                            <th>Date</th>
                            <th>Memo</th>
                            <th>Status</th>
                            <th>Lines</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="journal in journals.data" :key="journal.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <Link
                                    :href="route('accounting.journal-entries.show', journal.id)"
                                    class="font-medium text-brand-navy hover:text-brand-orange"
                                >
                                    {{ journal.number }}
                                </Link>
                            </td>
                            <td class="admin-data-table__cell">{{ journal.entry_date }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ journal.memo || '—' }}</td>
                            <td class="admin-data-table__cell">
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                    {{ journal.status_label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">{{ journal.lines_count }}</td>
                            <td class="admin-data-table__cell text-gray-500">{{ formatDateTime(journal.created_at) }}</td>
                        </tr>
                        <tr v-if="!journals.data.length">
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No journal entries yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="journals" :links="journals.links" />
            </div>
        </div>
    </AdminLayout>
</template>
