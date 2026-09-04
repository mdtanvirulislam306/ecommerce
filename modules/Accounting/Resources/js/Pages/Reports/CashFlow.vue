<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineProps({ rows: Array, net: String });
</script>
<template>
  <Head title="Cash Flow" /><AdminLayout title="Cash Flow">
    <div class="mb-4"><Link :href="route('accounting.overview')" class="text-sm text-brand-navy hover:text-brand-orange">← Overview</Link></div>
    <div class="mb-4 admin-card"><div class="text-xs uppercase text-gray-500">Net cash movement</div><div class="text-2xl font-semibold text-brand-navy">{{ net }}</div></div>
    <div class="admin-card overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Period</th><th class="pb-2 text-right">Inflow</th><th class="pb-2 text-right">Outflow</th><th class="pb-2 text-right">Net</th></tr></thead>
    <tbody><tr v-for="r in rows" :key="r.period" class="border-t"><td class="py-2">{{ r.period }}</td><td class="py-2 text-right">{{ Number(r.inflow).toFixed(2) }}</td><td class="py-2 text-right">{{ Number(r.outflow).toFixed(2) }}</td><td class="py-2 text-right">{{ Number(r.net).toFixed(2) }}</td></tr>
    <tr v-if="!rows.length"><td colspan="4" class="py-10 text-center text-gray-500">No cash flow data.</td></tr></tbody></table></div>
  </AdminLayout>
</template>
