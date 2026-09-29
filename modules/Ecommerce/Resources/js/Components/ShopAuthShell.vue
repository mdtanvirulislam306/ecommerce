<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});

const page = usePage();
const shopName = computed(() => page.props.tenant?.name || 'our shop');

const benefits = [
    {
        title: 'Track every order',
        body: 'See live status from placed to delivered.',
        icon: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        title: 'Checkout in seconds',
        body: 'Your name, phone and address are filled in for you.',
        icon: 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
    },
    {
        title: 'Your full order history',
        body: 'Reorder favourites and find past receipts anytime.',
        icon: 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
    },
];
</script>

<template>
    <StorefrontLayout>
        <div class="w-full bg-gradient-to-b from-brand-orange/5 via-white to-white">
            <div class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6 sm:py-14 lg:px-8">
                <div class="grid overflow-hidden rounded-3xl bg-white shadow-xl shadow-brand-navy/5 ring-1 ring-black/5 lg:grid-cols-[1fr_1.15fr]">
                    <aside class="relative hidden overflow-hidden bg-brand-navy p-10 text-white lg:flex lg:flex-col">
                        <span class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-brand-orange/25 blur-2xl" />
                        <span class="pointer-events-none absolute -bottom-24 -left-16 h-72 w-72 rounded-full bg-brand-teal/20 blur-3xl" />

                        <div class="relative">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-orange">{{ shopName }}</p>
                            <h2 class="mt-3 text-3xl font-semibold leading-tight tracking-tight">Your orders,<br />all in one place.</h2>
                        </div>

                        <ul class="relative mt-10 space-y-6">
                            <li v-for="benefit in benefits" :key="benefit.title" class="flex gap-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15">
                                    <svg class="h-5 w-5 text-brand-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="benefit.icon" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold">{{ benefit.title }}</p>
                                    <p class="mt-0.5 text-sm text-white/65">{{ benefit.body }}</p>
                                </div>
                            </li>
                        </ul>

                        <p class="relative mt-auto pt-10 text-xs text-white/50">Your details are only used for your orders with {{ shopName }}.</p>
                    </aside>

                    <section class="p-6 sm:p-10">
                        <h1 class="text-2xl font-semibold tracking-tight text-brand-navy sm:text-3xl">{{ title }}</h1>
                        <p v-if="subtitle" class="mt-2 text-sm text-gray-500">{{ subtitle }}</p>
                        <div class="mt-8">
                            <slot />
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
