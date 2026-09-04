<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineProps({ income: Array, expense: Array, income_total: String, expense_total: String, net: String });
</script>
<template>
  <Head title="Profit & Loss" /><AdminLayout title="Profit & Loss">
    <div class="mb-4"><Link :href="route('accounting.overview')" class="text-sm text-brand-navy hover:text-brand-orange">← Overview</Link></div>
    <div class="mb-4 admin-card"><div class="text-xs uppercase text-gray-500">Net</div><div class="text-2xl font-semibold text-brand-navy">{{ net }}</div></div>
    <div class="grid gap-6 lg:grid-cols-2">
      <section class="admin-card"><h2 class="mb-3 text-sm font-semibold">Income ({{ income_total }})</h2><table class="min-w-full text-sm"><tbody><tr v-for="r in income" :key="r.id" class="border-t"><td class="py-2">{{ r.code }} {{ r.name }}</td><td class="py-2 text-right">{{ Number(r.balance).toFixed(2) }}</td></tr></tbody></table></section>
      <section class="admin-card"><h2 class="mb-3 text-sm font-semibold">Expenses ({{ expense_total }})</h2><table class="min-w-full text-sm"><tbody><tr v-for="r in expense" :key="r.id" class="border-t"><td class="py-2">{{ r.code }} {{ r.name }}</td><td class="py-2 text-right">{{ Number(r.balance).toFixed(2) }}</td></tr></tbody></table></section>
    </div>
  </AdminLayout>
</template>
