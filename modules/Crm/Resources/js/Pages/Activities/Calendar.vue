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
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-brand-navy hover:bg-gray-50"
                    @click="router.get(route('crm.activities.calendar'), { date: calendar.prev })"
                >
                    ← Prev
                </button>
                <h2 class="min-w-[12rem] text-center text-sm font-semibold text-brand-navy">{{ calendar.week_label }}</h2>
                <button
                    type="button"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-brand-navy hover:bg-gray-50"
                    @click="router.get(route('crm.activities.calendar'), { date: calendar.next })"
                >
                    Next →
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-brand-navy px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-navy-dark"
                    @click="router.get(route('crm.activities.calendar'), { date: calendar.today })"
                >
                    Today
                </button>
            </div>
            <Link :href="route('crm.activities.all')" class="text-sm font-medium text-brand-orange hover:underline">All activities</Link>
        </div>

        <div class="grid gap-3 md:grid-cols-7">
            <section
                v-for="day in calendar.days"
                :key="day.date"
                class="min-h-40 rounded-xl border bg-white p-3 shadow-card"
                :class="day.is_today ? 'border-brand-orange ring-1 ring-brand-orange/20' : 'border-gray-200'"
            >
                <h3 class="mb-2 flex items-center justify-between text-xs font-semibold uppercase tracking-wide" :class="day.is_today ? 'text-brand-orange' : 'text-gray-500'">
                    <span>{{ day.label }}</span>
                    <span v-if="day.is_today" class="rounded-full bg-brand-orange/10 px-1.5 py-0.5 text-[10px] font-medium normal-case">Today</span>
                </h3>
                <div class="space-y-2">
                    <div
                        v-for="activity in day.activities"
                        :key="activity.id"
                        class="rounded-lg border border-gray-100 bg-gray-50 p-2 text-xs"
                        :class="activity.is_overdue && !activity.completed_at ? 'border-red-100 bg-red-50' : ''"
                    >
                        <div class="font-medium text-brand-navy">{{ activity.subject }}</div>
                        <div class="mt-0.5 text-gray-500">{{ activity.type_label }} · {{ activity.lead_name || activity.customer_name || '—' }}</div>
                    </div>
                    <p v-if="!day.activities.length" class="py-4 text-center text-xs text-gray-400">Free</p>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
