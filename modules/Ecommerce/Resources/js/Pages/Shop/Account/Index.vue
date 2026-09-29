<script setup>
import { districts } from '../../../Components/districts';
import ShopPasswordInput from '../../../Components/ShopPasswordInput.vue';
import InputError from '@/Components/InputError.vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { formatMoney } from '@/utils/formatMoney';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    profile: { type: Object, required: true },
    stats: { type: Object, required: true },
    orders: { type: Object, required: true },
});

const tabs = [
    { key: 'orders', label: 'My orders' },
    { key: 'details', label: 'Details & address' },
    { key: 'password', label: 'Password' },
];
const activeTab = ref('orders');

const firstName = computed(() => props.profile.name.split(' ')[0]);
const initials = computed(() =>
    props.profile.name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join(''),
);

const money = (amount, currency) => formatMoney(amount, currency || props.stats.currency);
const formatDate = (iso, withTime = false) =>
    iso
        ? new Date(iso).toLocaleDateString('en-BD', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
              ...(withTime ? { hour: 'numeric', minute: '2-digit' } : {}),
          })
        : '—';

const statusTone = {
    pending: 'bg-amber-50 text-amber-700 ring-amber-200',
    confirmed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    cancelled: 'bg-red-50 text-red-700 ring-red-200',
};
const paymentTone = {
    paid: 'text-emerald-600',
    pending: 'text-amber-600',
    failed: 'text-red-600',
    unpaid: 'text-gray-500',
};

const profileForm = useForm({
    name: props.profile.name,
    email: props.profile.email,
    phone: props.profile.phone ?? '',
    default_district: props.profile.default_district ?? 'Dhaka',
    default_address: props.profile.default_address ?? '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const saveProfile = () => {
    profileForm.put(route('shop.account.profile.update'), { preserveScroll: true, preserveState: true });
};

const savePassword = () => {
    passwordForm.put(route('shop.account.password.change'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => passwordForm.reset('current_password'),
    });
};

const inputClass = 'mt-1 block w-full rounded-xl border-gray-200 text-sm text-brand-navy placeholder:text-gray-400 focus:border-brand-teal focus:ring-brand-teal';
</script>

<template>
    <Head title="My account" />

    <StorefrontLayout>
        <div class="w-full bg-gradient-to-b from-brand-orange/5 via-white to-white">
            <div class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
                <section class="flex flex-col gap-5 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5 sm:flex-row sm:items-center sm:justify-between sm:p-8">
                    <div class="flex items-center gap-4">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-orange to-brand-orange-dark text-lg font-bold text-white shadow-lg shadow-brand-orange/25">
                            {{ initials }}
                        </span>
                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-brand-navy">Hi, {{ firstName }}</h1>
                            <p class="text-sm text-gray-500">{{ profile.email }}</p>
                        </div>
                    </div>
                    <Link
                        :href="route('shop.account.logout')"
                        method="post"
                        as="button"
                        class="inline-flex items-center justify-center gap-2 self-start rounded-xl px-4 py-2 text-sm font-medium text-gray-600 ring-1 ring-gray-200 transition hover:bg-gray-50 hover:text-brand-navy sm:self-auto"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                        Sign out
                    </Link>
                </section>

                <div class="mt-4 grid grid-cols-3 gap-3 sm:gap-4">
                    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5 sm:p-5">
                        <p class="text-xs font-medium text-gray-500">Orders</p>
                        <p class="mt-1 text-xl font-semibold text-brand-navy sm:text-2xl">{{ stats.orders }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5 sm:p-5">
                        <p class="text-xs font-medium text-gray-500">Total spent</p>
                        <p class="mt-1 truncate text-xl font-semibold text-brand-navy sm:text-2xl">{{ money(stats.spent) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5 sm:p-5">
                        <p class="text-xs font-medium text-gray-500">Member since</p>
                        <p class="mt-1 truncate text-xl font-semibold text-brand-navy sm:text-2xl">{{ formatDate(stats.member_since) }}</p>
                    </div>
                </div>

                <nav class="mt-8 flex gap-1 overflow-x-auto rounded-2xl bg-gray-100/80 p-1" aria-label="Account sections">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="flex-1 whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-semibold transition"
                        :class="activeTab === tab.key ? 'bg-white text-brand-navy shadow-sm' : 'text-gray-500 hover:text-brand-navy'"
                        @click="activeTab = tab.key"
                    >
                        {{ tab.label }}
                    </button>
                </nav>

                <section v-if="activeTab === 'orders'" class="mt-6">
                    <div v-if="!orders.data.length" class="rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-black/5">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-orange/10 text-brand-orange">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 10-8 0v4M5 9h14l-1.2 11.1a2 2 0 01-2 1.9H8.2a2 2 0 01-2-1.9L5 9z" />
                            </svg>
                        </span>
                        <h2 class="mt-4 text-lg font-semibold text-brand-navy">No orders yet</h2>
                        <p class="mt-1 text-sm text-gray-500">When you place an order while signed in, it shows up here.</p>
                        <Link
                            :href="route('shop.index')"
                            class="mt-6 inline-flex items-center gap-2 rounded-xl bg-brand-orange px-6 py-3 text-sm font-semibold text-white shadow-sm shadow-brand-orange/30 transition hover:bg-brand-orange-dark"
                        >
                            Start shopping
                        </Link>
                    </div>

                    <ul v-else class="space-y-3">
                        <li v-for="order in orders.data" :key="order.id">
                            <Link
                                :href="order.tracking_url"
                                class="group flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md hover:ring-brand-orange/30 sm:flex-row sm:items-center"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-brand-navy">{{ order.number }}</p>
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1" :class="statusTone[order.status]">
                                            {{ order.status_label }}
                                        </span>
                                        <span class="text-xs font-medium" :class="paymentTone[order.payment_status]">· {{ order.payment_status_label }}</span>
                                    </div>
                                    <p class="mt-1 truncate text-sm text-gray-600">
                                        {{ order.item_names.join(', ') }}<span v-if="order.more_items" class="text-gray-400"> +{{ order.more_items }} more</span>
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400">{{ formatDate(order.created_at, true) }} · {{ order.items_count }} {{ order.items_count === 1 ? 'item' : 'items' }}</p>
                                </div>
                                <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end sm:gap-1">
                                    <p class="text-lg font-semibold text-brand-navy">{{ money(order.grand_total, order.currency) }}</p>
                                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-brand-orange">
                                        Track order
                                        <svg class="h-4 w-4 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </span>
                                </div>
                            </Link>
                        </li>
                    </ul>

                    <div v-if="orders.last_page > 1" class="mt-6 flex items-center justify-between text-sm">
                        <Link
                            v-if="orders.prev_page_url"
                            :href="orders.prev_page_url"
                            preserve-scroll
                            class="rounded-xl px-4 py-2 font-medium text-brand-navy ring-1 ring-gray-200 hover:bg-gray-50"
                        >
                            ← Newer
                        </Link>
                        <span v-else />
                        <span class="text-gray-500">Page {{ orders.current_page }} of {{ orders.last_page }}</span>
                        <Link
                            v-if="orders.next_page_url"
                            :href="orders.next_page_url"
                            preserve-scroll
                            class="rounded-xl px-4 py-2 font-medium text-brand-navy ring-1 ring-gray-200 hover:bg-gray-50"
                        >
                            Older →
                        </Link>
                        <span v-else />
                    </div>
                </section>

                <form v-else-if="activeTab === 'details'" class="mt-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5 sm:p-8" @submit.prevent="saveProfile">
                    <h2 class="text-base font-semibold text-brand-navy">Your details</h2>
                    <p class="mt-1 text-sm text-gray-500">Used to fill in checkout for you.</p>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <label class="block sm:col-span-2">
                            <span class="text-xs font-medium text-gray-600">Full name</span>
                            <input v-model="profileForm.name" type="text" autocomplete="name" required :class="inputClass" />
                            <InputError class="mt-1" :message="profileForm.errors.name" />
                        </label>
                        <label class="block">
                            <span class="text-xs font-medium text-gray-600">Email</span>
                            <input v-model="profileForm.email" type="email" autocomplete="email" required :class="inputClass" />
                            <InputError class="mt-1" :message="profileForm.errors.email" />
                        </label>
                        <label class="block">
                            <span class="text-xs font-medium text-gray-600">Phone</span>
                            <input v-model="profileForm.phone" type="tel" inputmode="tel" autocomplete="tel" :class="inputClass" placeholder="01XXXXXXXXX" />
                            <InputError class="mt-1" :message="profileForm.errors.phone" />
                        </label>
                    </div>

                    <h3 class="mt-8 border-t border-gray-100 pt-6 text-base font-semibold text-brand-navy">Delivery address</h3>
                    <div class="mt-4 grid gap-5">
                        <label class="block sm:max-w-xs">
                            <span class="text-xs font-medium text-gray-600">District</span>
                            <select v-model="profileForm.default_district" :class="inputClass">
                                <option v-for="district in districts" :key="district" :value="district">{{ district }}</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-medium text-gray-600">Full address</span>
                            <textarea v-model="profileForm.default_address" rows="3" :class="inputClass" placeholder="House, road, area, landmark" />
                            <InputError class="mt-1" :message="profileForm.errors.default_address" />
                        </label>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button
                            type="submit"
                            class="rounded-xl bg-brand-orange px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-orange/30 transition hover:bg-brand-orange-dark disabled:opacity-60"
                            :disabled="profileForm.processing"
                        >
                            Save details
                        </button>
                    </div>
                </form>

                <form v-else class="mt-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5 sm:p-8" @submit.prevent="savePassword">
                    <h2 class="text-base font-semibold text-brand-navy">Change password</h2>
                    <p class="mt-1 text-sm text-gray-500">Use at least 8 characters.</p>

                    <div class="mt-6 grid max-w-md gap-5">
                        <div>
                            <label for="current_password" class="text-xs font-medium text-gray-600">Current password</label>
                            <ShopPasswordInput id="current_password" v-model="passwordForm.current_password" />
                            <InputError class="mt-1" :message="passwordForm.errors.current_password" />
                        </div>
                        <div>
                            <label for="new_password" class="text-xs font-medium text-gray-600">New password</label>
                            <ShopPasswordInput id="new_password" v-model="passwordForm.password" autocomplete="new-password" />
                            <InputError class="mt-1" :message="passwordForm.errors.password" />
                        </div>
                        <div>
                            <label for="new_password_confirmation" class="text-xs font-medium text-gray-600">Confirm new password</label>
                            <ShopPasswordInput id="new_password_confirmation" v-model="passwordForm.password_confirmation" autocomplete="new-password" />
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button
                            type="submit"
                            class="rounded-xl bg-brand-navy px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-navy/90 disabled:opacity-60"
                            :disabled="passwordForm.processing"
                        >
                            Update password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </StorefrontLayout>
</template>
