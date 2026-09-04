<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});
const search = ref(props.filters.search ?? '');
watch(search, (value) => {
    router.get(route('settings.users.index'), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>
<template>
    <Head title="Users" />
    <AdminLayout title="Users">
        <div class="mb-4"><TextInput v-model="search" type="search" class="w-64" placeholder="Search users…" /></div>
        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Name</th><th class="pb-2">Email</th><th class="pb-2">Joined</th></tr></thead>
                <tbody>
                    <tr v-for="row in users.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2 font-medium text-brand-navy">{{ row.name }}</td>
                        <td class="py-2">{{ row.email }}</td>
                        <td class="py-2">{{ row.created_at }}</td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="users" class="mt-4" />
        </section>
    </AdminLayout>
</template>