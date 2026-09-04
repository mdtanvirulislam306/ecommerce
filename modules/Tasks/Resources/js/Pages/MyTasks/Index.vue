<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    tasks: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');
watch(search, (value) => {
    router.get(route('tasks.my-tasks.index'), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>

<template>
    <Head title="My Tasks" />
    <AdminLayout title="My Tasks">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search…" />
            <Link :href="route('tasks.create')"><PrimaryButton type="button">Create task</PrimaryButton></Link>
        </div>
        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500">
                    <tr><th class="pb-2">Title</th><th class="pb-2">Status</th><th class="pb-2">Due</th></tr>
                </thead>
                <tbody>
                    <tr v-for="row in tasks.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2 font-medium text-brand-navy">{{ row.title }}</td>
                        <td class="py-2">{{ row.status }}</td>
                        <td class="py-2">{{ row.due_at || '—' }}</td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="tasks" class="mt-4" />
        </section>
    </AdminLayout>
</template>
