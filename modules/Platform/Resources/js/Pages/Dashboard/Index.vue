<script setup>
import PlatformShell from '../../Components/PlatformShell.vue';
import { formatMoney } from '@/utils/formatMoney';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    shops: { type: Object, required: true },
    revenue: { type: Object, required: true },
    users: { type: Object, required: true },
    plan_mix: { type: Array, default: () => [] },
    attention: { type: Array, default: () => [] },
    recent_shops: { type: Array, default: () => [] },
    usage: { type: Array, default: () => [] },
    expiringWithinDays: { type: Number, default: 14 },
});

const page = usePage();
const firstName = computed(() => (page.props.auth?.user?.name ?? '').split(' ')[0]);

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) {
        return 'Good morning';
    }
    return hour < 18 ? 'Good afternoon' : 'Good evening';
});

const money = (minor, currency = props.revenue.currency) => formatMoney(minor / 100, currency, { decimals: 0 });

const shopSegments = computed(() => {
    const total = Math.max(props.shops.total, 1);
    return [
        { key: 'active', label: 'Active', count: props.shops.active, tone: 'bg-emerald-500', width: (props.shops.active / total) * 100 },
        { key: 'trial', label: 'Trial', count: props.shops.trial, tone: 'bg-brand-teal', width: (props.shops.trial / total) * 100 },
        { key: 'suspended', label: 'Suspended', count: props.shops.suspended, tone: 'bg-red-400', width: (props.shops.suspended / total) * 100 },
    ];
});

const planShopsTotal = computed(() => Math.max(props.plan_mix.reduce((sum, plan) => sum + plan.shops, 0), 1));
const planTones = ['bg-brand-navy', 'bg-brand-orange', 'bg-brand-teal', 'bg-indigo-500', 'bg-rose-500', 'bg-emerald-500'];

const attentionTone = {
    expired: 'bg-red-50 text-red-700 ring-red-200',
    expiring: 'bg-amber-50 text-amber-700 ring-amber-200',
    no_plan: 'bg-gray-100 text-gray-600 ring-gray-200',
    suspended: 'bg-red-50 text-red-600 ring-red-100',
};

const formatDate = (iso) => (iso ? new Date(iso).toLocaleDateString('en-BD', { day: 'numeric', month: 'short', year: 'numeric' }) : null);

const attentionDetail = (row) => {
    const date = formatDate(row.date);
    return {
        expired: `Ended ${date}`,
        expiring: `Ends ${date}`,
        no_plan: 'Assign a plan to unlock their modules',
        suspended: date ? `Since ${date}` : 'Shop is offline',
    }[row.reason];
};

const statusPill = (status) =>
    status === 'suspended' ? 'bg-red-50 text-red-700' : status === 'trial' ? 'bg-brand-teal/15 text-brand-navy' : 'bg-emerald-50 text-emerald-700';

const maxRecentOrders = computed(() => Math.max(...props.usage.map((row) => row.orders_recent), 1));
</script>

<template>
    <PlatformShell title="Overview" full-width>
        <section class="relative overflow-hidden rounded-2xl bg-brand-navy p-6 text-white shadow-sm sm:p-8">
            <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-brand-teal/20 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-24 right-40 h-56 w-56 rounded-full bg-brand-orange/20 blur-3xl" />
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-white/60">{{ greeting }}, {{ firstName }}</p>
                    <h1 class="mt-1 text-2xl font-semibold sm:text-3xl">Your platform at a glance</h1>
                    <p class="mt-2 max-w-xl text-sm text-white/70">
                        {{ shops.total }} {{ shops.total === 1 ? 'shop runs' : 'shops run' }} on the platform, bringing in
                        <span class="font-semibold text-white">{{ money(revenue.mrr) }}</span> every month.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('platform.tenants.create')"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-orange px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-black/10 transition hover:bg-brand-orange-dark"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        New shop
                    </Link>
                    <Link
                        :href="route('platform.plans.index')"
                        class="inline-flex items-center rounded-xl bg-white/10 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/20"
                    >
                        Manage plans
                    </Link>
                </div>
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="admin-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Monthly recurring revenue</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-orange/10 text-brand-orange">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-semibold text-brand-navy">{{ money(revenue.mrr) }}</p>
                <p class="mt-1 text-xs text-gray-500">
                    {{ money(revenue.mrr * 12) }} a year · {{ revenue.paying_shops }} paying {{ revenue.paying_shops === 1 ? 'shop' : 'shops' }}
                </p>
            </article>

            <article class="admin-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Shops</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-navy/10 text-brand-navy">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-semibold text-brand-navy">{{ shops.total }}</p>
                <div class="mt-3 flex h-1.5 overflow-hidden rounded-full bg-gray-100">
                    <span v-for="segment in shopSegments" :key="segment.key" :class="segment.tone" :style="{ width: `${segment.width}%` }" />
                </div>
                <p class="mt-2 flex flex-wrap gap-x-3 text-xs text-gray-500">
                    <span v-for="segment in shopSegments" :key="segment.key" class="inline-flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full" :class="segment.tone" />{{ segment.count }} {{ segment.label.toLowerCase() }}
                    </span>
                </p>
            </article>

            <article class="admin-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Shop users</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-teal/15 text-brand-teal-dark">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-semibold text-brand-navy">{{ users.total }}</p>
                <p class="mt-1 text-xs text-gray-500">
                    {{ users.active_recently }} signed in over the last {{ users.window_days }} days<span v-if="users.pending_invitations"> · {{ users.pending_invitations }} invited</span>
                </p>
            </article>

            <article class="admin-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">New this month</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-semibold text-brand-navy">{{ shops.new_this_month }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ shops.new_this_month === 1 ? 'shop has' : 'shops have' }} joined since the 1st</p>
            </article>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
            <section class="admin-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-brand-navy">Plan mix</h2>
                        <p class="text-xs text-gray-500">Shops with a running plan, and what each plan earns per month.</p>
                    </div>
                    <Link :href="route('platform.plans.index')" class="text-sm font-medium text-brand-orange hover:underline">Plans</Link>
                </div>

                <p v-if="!plan_mix.length" class="py-10 text-center text-sm text-gray-400">No plans yet.</p>

                <ul v-else class="mt-5 space-y-4">
                    <li v-for="(plan, index) in plan_mix" :key="plan.id">
                        <div class="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                            <p class="font-semibold text-brand-navy">
                                {{ plan.name }}
                                <span v-if="!plan.is_active" class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500">Hidden</span>
                            </p>
                            <p class="text-gray-500">
                                <span class="font-semibold text-brand-navy">{{ plan.shops }}</span> {{ plan.shops === 1 ? 'shop' : 'shops' }}
                                · {{ money(plan.mrr) }}/mo
                            </p>
                        </div>
                        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="planTones[index % planTones.length]"
                                :style="{ width: `${Math.max((plan.shops / planShopsTotal) * 100, plan.shops ? 4 : 0)}%` }"
                            />
                        </div>
                        <p class="mt-1 text-xs text-gray-400">{{ plan.price_monthly ? `${money(plan.price_monthly)} per shop` : 'Free' }}</p>
                    </li>
                </ul>
            </section>

            <section class="admin-card !p-0">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-brand-navy">Needs attention</h2>
                    <p class="text-xs text-gray-500">Lapsed plans, plans ending within {{ expiringWithinDays }} days, and offline shops.</p>
                </div>
                <div v-if="!attention.length" class="px-5 py-10 text-center">
                    <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <p class="mt-3 text-sm font-semibold text-brand-navy">All clear</p>
                    <p class="mt-1 text-xs text-gray-500">Every shop is running on a current plan.</p>
                </div>
                <ul v-else class="max-h-96 divide-y divide-gray-100 overflow-y-auto">
                    <li v-for="row in attention" :key="`${row.id}-${row.reason}`">
                        <Link :href="route('platform.tenants.show', row.id)" class="flex items-center gap-3 px-5 py-3 transition hover:bg-gray-50">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-brand-navy">{{ row.name }}</p>
                                <p class="text-xs text-gray-500">{{ attentionDetail(row) }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1" :class="attentionTone[row.reason]">{{ row.label }}</span>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
            <section class="admin-card !p-0">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-brand-navy">Busiest shops</h2>
                    <p class="text-xs text-gray-500">Ranked by orders over the last 30 days.</p>
                </div>
                <p v-if="!usage.length" class="px-5 py-10 text-center text-sm text-gray-400">No shops yet.</p>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                            <tr>
                                <th class="px-5 py-3">Shop</th>
                                <th class="px-3 py-3">Orders · 30 days</th>
                                <th class="px-3 py-3 text-right">All orders</th>
                                <th class="px-3 py-3 text-right">Products</th>
                                <th class="px-5 py-3 text-right">Users</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="row in usage" :key="row.id" class="hover:bg-gray-50/70">
                                <td class="px-5 py-3">
                                    <Link :href="route('platform.tenants.show', row.id)" class="font-semibold text-brand-navy hover:text-brand-orange">{{ row.name }}</Link>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-gray-100">
                                            <div class="h-full rounded-full bg-brand-teal" :style="{ width: `${(row.orders_recent / maxRecentOrders) * 100}%` }" />
                                        </div>
                                        <span class="font-semibold text-brand-navy">{{ row.orders_recent }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-right text-gray-600">{{ row.orders.toLocaleString() }}</td>
                                <td class="px-3 py-3 text-right text-gray-600">{{ row.products.toLocaleString() }}</td>
                                <td class="px-5 py-3 text-right text-gray-600">{{ row.users }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="admin-card !p-0">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-brand-navy">Newest shops</h2>
                        <p class="text-xs text-gray-500">The latest to join.</p>
                    </div>
                    <Link :href="route('platform.tenants.index')" class="text-sm font-medium text-brand-orange hover:underline">All shops</Link>
                </div>
                <p v-if="!recent_shops.length" class="px-5 py-10 text-center text-sm text-gray-400">No shops yet.</p>
                <ul v-else class="divide-y divide-gray-100">
                    <li v-for="shop in recent_shops" :key="shop.id">
                        <Link :href="route('platform.tenants.show', shop.id)" class="flex items-center gap-3 px-5 py-3 transition hover:bg-gray-50">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-navy/10 text-xs font-bold text-brand-navy">
                                {{ shop.name.slice(0, 2).toUpperCase() }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-brand-navy">{{ shop.name }}</p>
                                <p class="truncate text-xs text-gray-500">{{ shop.plan_name || 'No plan' }} · {{ formatDate(shop.created_at) }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold capitalize" :class="statusPill(shop.status)">{{ shop.status }}</span>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </PlatformShell>
</template>
