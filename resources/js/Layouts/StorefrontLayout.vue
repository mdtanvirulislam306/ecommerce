<script setup>
import ShopCartDrawer from '../../../modules/Ecommerce/Resources/js/Components/ShopCartDrawer.vue';
import ShopCheckoutModal from '../../../modules/Ecommerce/Resources/js/Components/ShopCheckoutModal.vue';
import ShopOrderSuccessModal from '../../../modules/Ecommerce/Resources/js/Components/ShopOrderSuccessModal.vue';
import ShopProductModal from '../../../modules/Ecommerce/Resources/js/Components/ShopProductModal.vue';
import ShopRequestProgress from '../../../modules/Ecommerce/Resources/js/Components/ShopRequestProgress.vue';
import { useShopUi } from '@/Composables/useShopUi';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    search: { type: String, default: '' },
    dense: { type: Boolean, default: false },
});

const page = usePage();
const cartCount = computed(() => page.props.cartCount ?? page.props.shopCart?.count ?? 0);
const cartSubtotal = computed(() => Number(page.props.shopCart?.subtotal || 0));
const cartItemsLabel = computed(() => {
    const count = Number(cartCount.value) || 0;
    return `${count} ${count === 1 ? 'item' : 'items'}`;
});
const cartTotalLabel = computed(() =>
    Math.round(cartSubtotal.value).toLocaleString('en-BD'),
);
const flash = computed(() => page.props.flash);
const query = ref(props.search ?? '');
const searching = ref(false);
const { openCart, setCartButtonEl, state } = useShopUi();

/** @type {ReturnType<typeof setTimeout>|null} */
let searchTimer = null;
let ignoreQueryWatch = false;

watch(
    () => props.search,
    (value) => {
        const next = value ?? '';
        if (next === query.value) {
            return;
        }
        ignoreQueryWatch = true;
        query.value = next;
        ignoreQueryWatch = false;
    },
);

const runLiveSearch = (raw) => {
    const next = (raw || '').trim();
    const current = (props.search || '').trim();
    const onShopIndex = route().current('shop.index');

    if (next === current && onShopIndex) {
        searching.value = false;
        return;
    }

    searching.value = true;

    router.get(
        route('shop.index'),
        next ? { search: next } : {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                searching.value = false;
            },
        },
    );
};

watch(query, (value) => {
    if (ignoreQueryWatch) {
        return;
    }
    clearTimeout(searchTimer);
    searching.value = true;
    searchTimer = setTimeout(() => runLiveSearch(value), 320);
});

const submitSearch = () => {
    clearTimeout(searchTimer);
    runLiveSearch(query.value);
};

const clearSearch = () => {
    clearTimeout(searchTimer);
    ignoreQueryWatch = true;
    query.value = '';
    ignoreQueryWatch = false;
    runLiveSearch('');
};

onBeforeUnmount(() => {
    clearTimeout(searchTimer);
});
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
                            <svg
                                v-if="searching"
                                class="h-4 w-4 animate-spin text-brand-teal"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m1.7-4.3a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </span>
                        <input
                            v-model="query"
                            type="search"
                            placeholder="Search products…"
                            autocomplete="off"
                            class="w-full rounded-full border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-10 text-sm text-brand-navy placeholder:text-gray-400 transition focus:border-brand-teal focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-teal/30"
                        />
                        <button
                            v-if="query"
                            type="button"
                            class="absolute inset-y-0 right-2 my-auto inline-flex h-7 w-7 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-brand-navy"
                            aria-label="Clear search"
                            @click="clearSearch"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
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
                        <svg
                            v-if="searching"
                            class="h-4 w-4 animate-spin text-brand-teal"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m1.7-4.3a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </span>
                    <input
                        v-model="query"
                        type="search"
                        placeholder="Search products…"
                        autocomplete="off"
                        class="w-full rounded-full border border-gray-200 bg-gray-50 py-2 pl-9 pr-9 text-sm focus:border-brand-teal focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-teal/30"
                    />
                    <button
                        v-if="query"
                        type="button"
                        class="absolute inset-y-0 right-1.5 my-auto inline-flex h-7 w-7 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-brand-navy"
                        aria-label="Clear search"
                        @click="clearSearch"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </label>
            </form>
        </header>

        <main :class="cartCount > 0 ? 'pb-[4.75rem] lg:pb-0' : ''">
            <div v-if="flash?.success && !flash?.order_placed" class="w-full px-4 pt-4 sm:px-6 lg:px-8">
                <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-100">
                    {{ flash.success }}
                </div>
            </div>
            <slot />
        </main>

        <!-- Mobile: full-width bottom bar. Desktop: right-middle card. -->
        <div
            v-show="cartCount > 0 && !state.cartOpen"
            class="fixed inset-x-0 bottom-0 z-50 bg-brand-navy pb-[env(safe-area-inset-bottom)] lg:inset-x-auto lg:bottom-auto lg:right-0 lg:top-1/2 lg:bg-transparent lg:pb-0 lg:-translate-y-1/2"
        >
            <button
                id="shop-cart-target"
                data-shop-cart-target
                :ref="setCartButtonEl"
                type="button"
                class="flex w-full items-stretch overflow-hidden shadow-[0_-6px_24px_rgba(44,75,96,0.18)] transition hover:brightness-105 active:brightness-95 lg:w-[4.75rem] lg:flex-col lg:rounded-l-2xl lg:shadow-xl lg:shadow-brand-navy/25 lg:active:scale-[0.98]"
                aria-label="Open cart"
                @click="openCart"
            >
                <span class="flex min-w-0 flex-1 items-center gap-2.5 bg-brand-orange px-4 py-3 text-white lg:flex-col lg:gap-1 lg:px-2 lg:py-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/15 ring-1 ring-white/25">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 10-8 0v4M5 9h14l-1.2 11.1a2 2 0 01-2 1.9H8.2a2 2 0 01-2-1.9L5 9z" />
                        </svg>
                    </span>
                    <span class="min-w-0 truncate text-sm font-bold italic leading-tight lg:text-[11px]">{{ cartItemsLabel }}</span>
                </span>
                <span class="flex shrink-0 items-center bg-brand-navy px-5 py-3 text-sm font-bold leading-none text-white lg:w-full lg:justify-center lg:px-2 lg:py-2.5 lg:text-[12px]">
                    ৳{{ cartTotalLabel }}
                </span>
            </button>
        </div>

        <ShopRequestProgress />
        <ShopCartDrawer />
        <ShopCheckoutModal />
        <ShopOrderSuccessModal />
        <ShopProductModal />
    </div>
</template>
