<script setup>
import ShopProductCard from '../../Components/ShopProductCard.vue';
import ShopStoryViewer from '../../Components/ShopStoryViewer.vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    story_groups: { type: Array, default: () => [] },
    trending: { type: Array, default: () => [] },
    homepage: { type: Array, default: () => [] },
    best_selling: { type: Array, default: () => [] },
    search: { type: String, default: null },
    search_results: { type: Array, default: null },
    category: { type: Object, default: null },
    category_products: { type: Array, default: null },
    child_categories: { type: Array, default: () => [] },
});

const isSearching = computed(() => Boolean(props.search));
const isCategoryView = computed(() => Boolean(props.category));
const storyViewerOpen = ref(false);
const activeGroupStories = ref([]);

const TITLE_MAX = 36;

const truncateTitle = (value) => {
    const title = (value || '').trim();
    if (!title) {
        return '';
    }
    if (title.length <= TITLE_MAX) {
        return title;
    }
    return `${title.slice(0, TITLE_MAX).trimEnd()}...`;
};

const openStoryGroup = (group) => {
    activeGroupStories.value = group.stories || [];
    storyViewerOpen.value = true;
};

const closeStory = () => {
    storyViewerOpen.value = false;
    activeGroupStories.value = [];
};

const categoryHref = (slug) => route('shop.index', { category: slug });

const isActiveCategory = (slug) => props.category?.slug === slug || props.category?.root_slug === slug;

const productGridClass = 'grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 lg:gap-4';
</script>

<template>
    <Head :title="isSearching ? `Search: ${search}` : (category ? category.name : 'Shop')" />

    <StorefrontLayout :search="search || ''">
        <div class="w-full lg:flex lg:items-start">
            <aside class="hidden shrink-0 self-start border-r border-gray-100 bg-white lg:sticky lg:top-[4.4375rem] lg:flex lg:max-h-[calc(100vh-4.4375rem)] lg:w-56 lg:flex-col xl:w-60">
                <div class="flex min-h-0 flex-1 flex-col px-3 pb-2 pt-3">
                    <h2 class="mb-2 shrink-0 text-xs font-semibold uppercase tracking-wider text-gray-400">Categories</h2>
                    <nav class="min-h-0 flex-1 space-y-0.5 overflow-y-auto overscroll-contain">
                        <Link
                            :href="route('shop.index')"
                            class="flex items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition"
                            :class="!search && !category ? 'bg-brand-orange/10 text-brand-orange' : 'text-brand-navy hover:bg-gray-50'"
                        >
                            <span>All products</span>
                        </Link>
                        <Link
                            v-for="item in categories"
                            :key="item.id"
                            :href="categoryHref(item.slug)"
                            class="flex items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-sm transition"
                            :class="isActiveCategory(item.slug) ? 'bg-brand-orange/10 font-medium text-brand-orange' : 'text-brand-navy hover:bg-gray-50'"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <img
                                    v-if="item.image_url"
                                    :src="item.image_url"
                                    alt=""
                                    class="h-6 w-6 shrink-0 rounded-md object-cover"
                                />
                                <span class="truncate">{{ item.name }}</span>
                            </span>
                            <span class="shrink-0 text-[11px] text-gray-400">{{ item.product_count }}</span>
                        </Link>
                        <p v-if="!categories.length" class="px-3 py-6 text-center text-xs text-gray-400">
                            No active categories yet.
                        </p>
                    </nav>
                </div>
            </aside>

            <div class="min-w-0 flex-1 space-y-8 px-4 py-5 sm:px-6 lg:px-8 lg:py-3">
                <div class="flex gap-2 overflow-x-auto pb-1 lg:hidden">
                    <Link
                        :href="route('shop.index')"
                        class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-medium ring-1"
                        :class="!search && !category ? 'bg-brand-orange text-white ring-brand-orange' : 'bg-white text-brand-navy ring-gray-200'"
                    >
                        All
                    </Link>
                    <Link
                        v-for="item in categories"
                        :key="`m-${item.id}`"
                        :href="categoryHref(item.slug)"
                        class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-medium ring-1"
                        :class="isActiveCategory(item.slug) ? 'bg-brand-orange text-white ring-brand-orange' : 'bg-white text-brand-navy ring-gray-200'"
                    >
                        {{ item.name }}
                    </Link>
                </div>

                <section v-if="isSearching">
                    <div class="mb-4 flex items-end justify-between gap-3">
                        <div>
                            <h1 class="text-xl font-semibold text-brand-navy">Results for “{{ search }}”</h1>
                            <p class="mt-1 text-sm text-gray-500">{{ search_results?.length || 0 }} products found</p>
                        </div>
                        <Link :href="route('shop.index')" class="text-sm font-medium text-brand-orange hover:underline">Clear</Link>
                    </div>
                    <div :class="productGridClass">
                        <ShopProductCard
                            v-for="product in search_results"
                            :key="product.id"
                            :product="product"
                        />
                    </div>
                    <p v-if="!search_results?.length" class="rounded-2xl border border-dashed border-gray-200 py-16 text-center text-sm text-gray-500">
                        No products match your search.
                    </p>
                </section>

                <section v-else-if="isCategoryView" class="space-y-4">
                    <div v-if="child_categories.length" class="flex gap-2 overflow-x-auto pb-0.5">
                        <Link
                            v-for="child in child_categories"
                            :key="child.id"
                            :href="categoryHref(child.slug)"
                            class="group flex shrink-0 items-center gap-2 rounded-full border border-gray-100 bg-white py-1 pl-1 pr-3 shadow-card transition hover:-translate-y-px hover:border-brand-orange/40 hover:shadow-md"
                        >
                            <span class="relative flex h-9 w-9 shrink-0 overflow-hidden rounded-full bg-gradient-to-br from-brand-orange to-brand-teal ring-2 ring-brand-orange/15 ring-offset-1 ring-offset-white">
                                <img
                                    v-if="child.image_url"
                                    :src="child.image_url"
                                    :alt="child.name"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else class="flex h-full w-full items-center justify-center text-[11px] font-semibold text-white">
                                    {{ child.name.charAt(0) }}
                                </span>
                            </span>
                            <span class="min-w-0 pr-0.5">
                                <span class="block max-w-[7.5rem] truncate text-xs font-semibold text-brand-navy group-hover:text-brand-orange">
                                    {{ child.name }}
                                </span>
                                <span class="block text-[10px] leading-none text-gray-400">
                                    {{ child.product_count }} {{ child.product_count === 1 ? 'item' : 'items' }}
                                </span>
                            </span>
                        </Link>
                    </div>
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <p v-if="category.parent_slug" class="mb-1 text-xs text-gray-400">
                                <Link :href="categoryHref(category.parent_slug)" class="hover:text-brand-orange">
                                    {{ category.parent_name }}
                                </Link>
                                <span class="mx-1">/</span>
                                <span>{{ category.name }}</span>
                            </p>
                            <h1 class="text-xl font-semibold text-brand-navy">{{ category.name }}</h1>
                            <p class="mt-1 text-sm text-gray-500">{{ category_products?.length || 0 }} products</p>
                        </div>
                        <Link :href="route('shop.index')" class="text-sm font-medium text-brand-orange hover:underline">All products</Link>
                    </div>
                    <div :class="productGridClass">
                        <ShopProductCard
                            v-for="product in category_products"
                            :key="product.id"
                            :product="product"
                        />
                    </div>
                    <p v-if="!category_products?.length" class="rounded-2xl border border-dashed border-gray-200 py-16 text-center text-sm text-gray-500">
                        No published products in this category.
                    </p>
                </section>

                <template v-else>
                    <section v-if="story_groups.length">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-brand-navy">Stories</h2>
                        </div>
                        <div class="flex gap-3 overflow-x-auto pb-2">
                            <button
                                v-for="group in story_groups"
                                :key="group.key"
                                type="button"
                                class="group relative w-28 shrink-0 overflow-hidden rounded-2xl bg-gray-100 text-left shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:ring-brand-orange/40 sm:w-32"
                                @click="openStoryGroup(group)"
                            >
                                <span class="block aspect-[3/4] overflow-hidden bg-brand-navy/5">
                                    <video
                                        v-if="group.cover?.type === 'video'"
                                        :src="group.cover.media_url"
                                        class="h-full w-full object-cover"
                                        muted
                                        playsinline
                                        preload="metadata"
                                    />
                                    <img
                                        v-else
                                        :src="group.cover?.media_url"
                                        :alt="group.label"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.04]"
                                        loading="lazy"
                                    />
                                </span>
                                <span
                                    v-if="group.count > 1"
                                    class="absolute right-2 top-2 rounded-full bg-brand-orange px-1.5 py-0.5 text-[10px] font-semibold text-white"
                                >
                                    {{ group.count }}
                                </span>
                                <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-brand-navy/80 to-transparent px-2.5 pb-2.5 pt-8">
                                    <span class="line-clamp-2 text-[11px] font-medium leading-snug text-white">
                                        {{ truncateTitle(group.label) }}
                                    </span>
                                </span>
                            </button>
                        </div>
                    </section>

                    <section>
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-lg font-semibold tracking-tight text-brand-navy">Trending Now</h2>
                        </div>
                        <div :class="productGridClass">
                            <ShopProductCard
                                v-for="product in trending"
                                :key="`trend-${product.id}`"
                                :product="product"
                            />
                        </div>
                        <p v-if="!trending.length" class="py-10 text-center text-sm text-gray-500">
                            Mark products as Featured in Ecommerce → Online Products.
                        </p>
                    </section>

                    <section class="grid gap-4 md:grid-cols-2">
                        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#fff1e8] via-[#ffe4d4] to-brand-orange/40 p-6 sm:p-8">
                            <div class="relative z-10 max-w-[16rem]">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-orange-dark">Limited offer</p>
                                <h3 class="mt-2 text-2xl font-semibold leading-tight text-brand-navy">Big Summer Sale</h3>
                                <p class="mt-2 text-sm text-brand-navy/70">Up to 50% off selected styles this week.</p>
                                <Link
                                    :href="route('shop.index')"
                                    class="mt-5 inline-flex rounded-xl bg-brand-orange px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-orange/30 transition hover:bg-brand-orange-dark"
                                >
                                    Shop Now
                                </Link>
                            </div>
                            <div class="pointer-events-none absolute -bottom-6 -right-4 h-36 w-36 rounded-full bg-white/40 blur-2xl" />
                        </div>

                        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#e8f8f8] via-[#d5f3f4] to-brand-teal/50 p-6 sm:p-8">
                            <div class="relative z-10 max-w-[16rem]">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-teal-dark">Just dropped</p>
                                <h3 class="mt-2 text-2xl font-semibold leading-tight text-brand-navy">New Arrivals</h3>
                                <p class="mt-2 text-sm text-brand-navy/70">Fresh picks curated for your storefront.</p>
                                <Link
                                    :href="route('shop.index')"
                                    class="mt-5 inline-flex rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-navy-dark"
                                >
                                    Shop Now
                                </Link>
                            </div>
                            <div class="pointer-events-none absolute -bottom-8 -right-2 h-40 w-40 rounded-full bg-white/50 blur-2xl" />
                        </div>
                    </section>

                    <section>
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-lg font-semibold tracking-tight text-brand-navy">Best Selling Products</h2>
                        </div>
                        <div :class="productGridClass">
                            <ShopProductCard
                                v-for="product in (homepage.length ? homepage : best_selling)"
                                :key="`best-${product.id}`"
                                :product="product"
                            />
                        </div>
                        <p
                            v-if="!(homepage.length || best_selling.length)"
                            class="rounded-2xl border border-dashed border-gray-200 py-16 text-center text-sm text-gray-500"
                        >
                            No published products yet. Publish from Admin → Ecommerce → Online Products.
                        </p>
                    </section>

                    <section v-if="homepage.length && best_selling.length">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-lg font-semibold tracking-tight text-brand-navy">More to explore</h2>
                        </div>
                        <div :class="productGridClass">
                            <ShopProductCard
                                v-for="product in best_selling"
                                :key="`more-${product.id}`"
                                :product="product"
                            />
                        </div>
                    </section>
                </template>
            </div>
        </div>

        <ShopStoryViewer
            :show="storyViewerOpen"
            :stories="activeGroupStories"
            @close="closeStory"
        />
    </StorefrontLayout>
</template>
