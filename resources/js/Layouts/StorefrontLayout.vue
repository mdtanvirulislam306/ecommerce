<script setup>
import ShopCartDrawer from '../../../modules/Ecommerce/Resources/js/Components/ShopCartDrawer.vue';
import ShopCheckoutModal from '../../../modules/Ecommerce/Resources/js/Components/ShopCheckoutModal.vue';
import ShopProductModal from '../../../modules/Ecommerce/Resources/js/Components/ShopProductModal.vue';
import ShopRequestProgress from '../../../modules/Ecommerce/Resources/js/Components/ShopRequestProgress.vue';
import { useShopUi } from '@/Composables/useShopUi';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    search: { type: String, default: '' },
    dense: { type: Boolean, default: false },
});

const page = usePage();
const cartCount = computed(() => page.props.cartCount ?? page.props.shopCart?.count ?? 0);
const flash = computed(() => page.props.flash);
const query = ref(props.search ?? '');
const { openCart, setCartButtonEl } = useShopUi();

watch(
    () => props.search,
    (value) => {
        query.value = value ?? '';
    },
);

const submitSearch = () => {
    router.get(
        route('shop.index'),
        { search: query.value || undefined },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <div class="min-h-screen bg-white text-brand-navy">
        <header class="sticky top-0 z-40 border-b border-gray-100 bg-white/95 backdrop-blur">
            <div
                class="flex w-full items-center gap-4 px-4 py-3 sm:gap-6 sm:px-6 lg:px-8"
                :class="dense ? 'py-2.5' : 'py-3.5'"
            >
                <Link :href="route('shop.index')" class="group flex shrink-0 items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-orange text-white shadow-sm shadow-brand-orange/30">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 10-8 0v4M5 9h14l-1.2 11.1a2 2 0 01-2 1.9H8.2a2 2 0 01-2-1.9L5 9z" />
                        </svg>
                    </span>
                    <span class="text-lg font-semibold tracking-tight text-brand-navy">
                        NexCore<span class="text-brand-orange">Shop</span>
                    </span>
                </Link>

                <form class="mx-auto hidden min-w-0 flex-1 max-w-3xl md:block" @submit.prevent="submitSearch">
                    <label class="relative block">
                        <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-gray-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m1.7-4.3a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </span>
                        <input
                            v-model="query"
                            type="search"
                            placeholder="Search products, brands…"
                            class="w-full rounded-full border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-brand-navy placeholder:text-gray-400 transition focus:border-brand-teal focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-teal/30"
                        />
                    </label>
                </form>

                <div class="ml-auto flex items-center gap-1 sm:gap-2">
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-50 hover:text-brand-orange"
                        title="Wishlist coming soon"
                        aria-label="Wishlist"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.3 12.3l7 7.2c.4.4 1 .4 1.4 0l7-7.2a4.8 4.8 0 00-6.8-6.8l-.9.9-.9-.9a4.8 4.8 0 00-6.8 6.8z" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="relative inline-flex h-10 items-center gap-2 rounded-full px-2.5 text-sm font-medium text-brand-navy transition hover:bg-brand-orange/10 hover:text-brand-orange sm:px-3"
                        @click="openCart"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l3-8H6.4M7 13L5.4 5M7 13l-2 6h14M10 19a1 1 0 100 2 1 1 0 000-2zm8 0a1 1 0 100 2 1 1 0 000-2z" />
                        </svg>
                        <span class="hidden sm:inline">Cart</span>
                        <span
                            v-if="cartCount"
                            class="absolute -right-0.5 -top-0.5 inline-flex min-w-5 items-center justify-center rounded-full bg-brand-orange px-1.5 py-0.5 text-[10px] font-semibold text-white sm:static sm:ml-0.5"
                        >
                            {{ cartCount }}
                        </span>
                    </button>
                </div>
            </div>

            <form class="border-t border-gray-50 px-4 py-2.5 md:hidden" @submit.prevent="submitSearch">
                <label class="relative block">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m1.7-4.3a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </span>
                    <input
                        v-model="query"
                        type="search"
                        placeholder="Search products…"
                        class="w-full rounded-full border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-sm focus:border-brand-teal focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-teal/30"
                    />
                </label>
            </form>
        </header>

        <main>
            <div v-if="flash?.success" class="w-full px-4 pt-4 sm:px-6 lg:px-8">
                <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-100">
                    {{ flash.success }}
                </div>
            </div>
            <slot />
        </main>

        <!-- Floating right cart button -->
        <button
            id="shop-cart-target"
            data-shop-cart-target
            :ref="setCartButtonEl"
            type="button"
            class="fixed bottom-6 right-4 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-brand-orange text-white shadow-lg shadow-brand-orange/40 transition hover:scale-105 hover:bg-brand-orange-dark sm:right-6"
            aria-label="Open cart"
            @click="openCart"
        >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l3-8H6.4M7 13L5.4 5M7 13l-2 6h14M10 19a1 1 0 100 2 1 1 0 000-2zm8 0a1 1 0 100 2 1 1 0 000-2z" />
            </svg>
            <span
                v-if="cartCount"
                class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-brand-navy px-1.5 py-0.5 text-[10px] font-semibold text-white"
            >
                {{ cartCount }}
            </span>
        </button>

        <ShopRequestProgress />
        <ShopCartDrawer />
        <ShopCheckoutModal />
        <ShopProductModal />
    </div>
</template>
