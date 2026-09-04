<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    sessions: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const visit = () => router.get(route('pos.session-history'), { search: search.value || undefined, per_page: perPage.value }, { preserveState: true, replace: true });
let t = null;
watch(search, () => { clearTimeout(t); t = setTimeout(visit, 300); });
</script>

<template>
    <Head title="Session History" />
    <AdminLayout title="Session History">
        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <h2 class="text-sm font-semibold text-brand-navy">Closed sessions</h2>
                <input v-model="search" type="search" placeholder="Search register…" class="admin-data-table__search" />
            </div>
            <table class="min-w-full">
                <thead><tr><th>Register</th><th>Opening</th><th>Closing</th><th>Expected</th><th>Sales</th><th>Closed</th></tr></thead>
                <tbody>
                    <tr v-for="session in sessions.data" :key="session.id">
                        <td class="font-medium text-brand-navy">{{ session.register_name }}</td>
                        <td>{{ Number(session.opening_cash).toFixed(2) }}</td>
                        <td>{{ session.closing_cash != null ? Number(session.closing_cash).toFixed(2) : '—' }}</td>
                        <td>{{ session.expected_cash != null ? Number(session.expected_cash).toFixed(2) : '—' }}</td>
                        <td>{{ Number(session.sales_total).toFixed(2) }}</td>
                        <td class="text-sm text-gray-500">{{ session.closed_at ? new Date(session.closed_at).toLocaleString() : '—' }}</td>
                    </tr>
                    <tr v-if="!sessions.data.length"><td colspan="6" class="py-10 text-center text-gray-500">No closed sessions yet.</td></tr>
                </tbody>
            </table>
            <TablePagination :paginator="sessions" :per-page="perPage" :per-page-options="perPageOptions" @change-page="(p) => router.get(route('pos.session-history'), { search: search || undefined, per_page: perPage, page: p }, { preserveState: true, replace: true })" @change-per-page="(v) => { perPage = v; visit(); }" />
        </div>
    </AdminLayout>
</template>
