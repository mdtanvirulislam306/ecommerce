<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    calendar: { type: Object, required: true },
});
</script>

<template>
    <Head title="Activities Calendar" />
    <AdminLayout title="Activities Calendar">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <button type="button" class="text-sm text-brand-navy hover:text-brand-orange" @click="router.get(route('crm.activities.calendar'), { date: calendar.prev })">← Prev</button>
                <h2 class="text-sm font-semibold text-brand-navy">{{ calendar.week_label }}</h2>
                <button type="button" class="text-sm text-brand-navy hover:text-brand-orange" @click="router.get(route('crm.activities.calendar'), { date: calendar.next })">Next →</button>
            </div>
            <Link :href="route('crm.activities.all')" class="text-sm text-brand-orange hover:underline">All activities</Link>
        </div>

        <div class="grid gap-3 md:grid-cols-7">
            <section v-for="day in calendar.days" :key="day.date" class="admin-card min-h-40">
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ day.label }}</h3>
                <div class="space-y-2">
                    <div v-for="activity in day.activities" :key="activity.id" class="rounded border border-gray-100 bg-white p-2 text-xs">
                        <div class="font-medium text-brand-navy">{{ activity.subject }}</div>
                        <div class="text-gray-500">{{ activity.type_label }} · {{ activity.lead_name || activity.customer_name || '—' }}</div>
                    </div>
                    <p v-if="!day.activities.length" class="text-xs text-gray-400">No activities</p>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
