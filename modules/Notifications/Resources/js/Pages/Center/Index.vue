<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { Head, router } from '@inertiajs/vue3';
defineProps({ notifications: { type: Object, required: true } });
</script>
<template>
    <Head title="Notification Center" />
    <AdminLayout title="Notification Center">
        <section class="admin-card">
            <ul class="divide-y divide-gray-100 text-sm">
                <li v-for="row in notifications.data" :key="row.id" class="flex items-start justify-between gap-3 py-3">
                    <div>
                        <p class="font-medium text-brand-navy">{{ row.title }}</p>
                        <p class="text-gray-500">{{ row.body }}</p>
                    </div>
                    <button v-if="!row.read_at" type="button" class="text-xs text-brand-orange" @click="router.post(route('notifications.center.read', row.id))">Mark read</button>
                    <span v-else class="text-xs text-gray-400">Read</span>
                </li>
            </ul>
            <TablePagination :paginator="notifications" class="mt-4" />
        </section>
    </AdminLayout>
</template>