<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineProps({ assets: Array, liabilities: Array, equity: Array, totals: Object });
</script>
<template>
  <Head title="Balance Sheet" /><AdminLayout title="Balance Sheet">
    <div class="mb-4"><Link :href="route('accounting.overview')" class="text-sm text-brand-navy hover:text-brand-orange">← Overview</Link></div>
    <div class="grid gap-6 lg:grid-cols-3">
      <section class="admin-card"><h2 class="mb-2 text-sm font-semibold">Assets ({{ totals.assets }})</h2><table class="min-w-full text-sm"><tbody><tr v-for="r in assets" :key="r.id" class="border-t"><td class="py-2">{{ r.name }}</td><td class="py-2 text-right">{{ Number(r.balance).toFixed(2) }}</td></tr></tbody></table></section>
      <section class="admin-card"><h2 class="mb-2 text-sm font-semibold">Liabilities ({{ totals.liabilities }})</h2><table class="min-w-full text-sm"><tbody><tr v-for="r in liabilities" :key="r.id" class="border-t"><td class="py-2">{{ r.name }}</td><td class="py-2 text-right">{{ Number(r.balance).toFixed(2) }}</td></tr></tbody></table></section>
      <section class="admin-card"><h2 class="mb-2 text-sm font-semibold">Equity ({{ totals.equity }})</h2><table class="min-w-full text-sm"><tbody><tr v-for="r in equity" :key="r.id" class="border-t"><td class="py-2">{{ r.name }}</td><td class="py-2 text-right">{{ Number(r.balance).toFixed(2) }}</td></tr></tbody></table></section>
    </div>
  </AdminLayout>
</template>
