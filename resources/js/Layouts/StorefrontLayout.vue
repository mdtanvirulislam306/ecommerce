<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const cartCount = computed(() => page.props.cartCount ?? 0);
const flash = computed(() => page.props.flash);
</script>

<template>
    <div class="min-h-screen bg-[#f7f5f1] text-brand-navy">
        <header class="border-b border-black/5 bg-white/80 backdrop-blur">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
                <Link :href="route('shop.index')" class="text-lg font-semibold tracking-tight">
                    NexCore Shop
                </Link>
                <nav class="flex items-center gap-4 text-sm">
                    <Link :href="route('shop.index')" class="hover:text-brand-orange">Products</Link>
                    <Link :href="route('shop.cart')" class="hover:text-brand-orange">
                        Cart
                        <span
                            v-if="cartCount"
                            class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-brand-navy px-1.5 text-[11px] text-white"
                        >
                            {{ cartCount }}
                        </span>
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            <div v-if="flash?.success" class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ flash.success }}
            </div>
            <slot />
        </main>
    </div>
</template>
