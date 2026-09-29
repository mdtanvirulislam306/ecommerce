<script setup>
import { useShopUi } from '@/composables/useShopUi';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const { state, closeProduct, openProductModal, openCheckout, flyToCart } = useShopUi();

const quantity = ref(1);
const selectedVariantId = ref(null);
const selectedOptions = ref({});
const activeImageIndex = ref(0);
const adding = ref(false);
const activeTab = ref('details');

const product = computed(() => state.product);

const selectedVariant = computed(() =>
    product.value?.variants?.find((v) => v.id === selectedVariantId.value) ?? null,
);

const gallery = computed(() => {
    if (selectedVariant.value?.media?.length) {
        return selectedVariant.value.media;
    }
    if (product.value?.media?.length) {
        return product.value.media;
    }
    const fallback = selectedVariant.value?.image_url || product.value?.image_url;
    return fallback ? [{ id: 0, url: fallback, alt: product.value?.name }] : [];
});

const activeImage = computed(() => gallery.value[activeImageIndex.value] ?? gallery.value[0] ?? null);

const displayPrice = computed(() => {
    if (selectedVariant.value?.price != null) {
        return { price: selectedVariant.value.price, currency: selectedVariant.value.currency };
    }
    return { price: product.value?.price, currency: product.value?.currency, message: product.value?.price_message };
});

const inStock = computed(() =>
    selectedVariant.value ? selectedVariant.value.in_stock : product.value?.in_stock,
);

const stockQty = computed(() => {
    const qty = selectedVariant.value?.stock_available ?? product.value?.stock_available;
    return qty == null ? null : Number(qty);
});

const attributeGroups = computed(() => {
    const groups = {};
    for (const variant of product.value?.variants || []) {
        for (const attr of variant.attributes || []) {
            if (!groups[attr.attribute_id]) {
                groups[attr.attribute_id] = {
                    id: attr.attribute_id,
                    name: attr.attribute_name,
                    options: [],
                };
            }
            if (!groups[attr.attribute_id].options.some((o) => o.id === attr.attribute_option_id)) {
                groups[attr.attribute_id].options.push({
                    id: attr.attribute_option_id,
                    value: attr.option_value,
                });
            }
        }
    }
    return Object.values(groups);
});

const reviews = computed(() => product.value?.reviews || []);

const starFill = computed(() => {
    const avg = Number(product.value?.rating_avg || 0);
    return Math.round(Math.min(5, Math.max(0, avg)));
});

const hasVariants = computed(() =>
    Boolean(product.value?.has_variants && (product.value?.variants?.length || 0) > 0),
);

watch(
    () => state.product,
    (value) => {
        quantity.value = 1;
        activeImageIndex.value = 0;
        adding.value = false;
        activeTab.value = 'details';
        const first = value?.variants?.[0];
        selectedVariantId.value = first?.id ?? null;
        const map = {};
        for (const attr of first?.attributes || []) {
            map[attr.attribute_id] = attr.attribute_option_id;
        }
        selectedOptions.value = map;
    },
    { immediate: true },
);

watch(gallery, () => {
    activeImageIndex.value = 0;
});

const pickOption = (attributeId, optionId) => {
    selectedOptions.value = { ...selectedOptions.value, [attributeId]: optionId };
    const match = (product.value?.variants || []).find((variant) =>
        (variant.attributes || []).every(
            (attr) => selectedOptions.value[attr.attribute_id] === attr.attribute_option_id,
        ),
    );
    if (match) {
        selectedVariantId.value = match.id;
    }
};

const selectVariantCard = (variant) => {
    selectedVariantId.value = variant.id;
    const map = {};
    for (const attr of variant.attributes || []) {
        map[attr.attribute_id] = attr.attribute_option_id;
    }
    selectedOptions.value = map;
};

const isColorGroup = (name) => /color|colour|shade/i.test(name || '');

const looksLikeColor = (value) => {
    const v = String(value || '').trim();
    return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v)
        || /^(rgb|hsl)a?\(/i.test(v)
        || /^(red|blue|green|black|white|yellow|orange|pink|purple|gray|grey|navy|teal|brown)$/i.test(v);
};

const colorStyle = (value) => ({ background: String(value || '').trim().toLowerCase() });

const canAdd = computed(() => {
    if (!product.value || !displayPrice.value.price || !inStock.value) {
        return false;
    }
    if (hasVariants.value && !selectedVariantId.value) {
        return false;
    }
    return true;
});

const formatReviewDate = (value) => {
    if (!value) {
        return '';
    }
    try {
        return new Date(value).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return '';
    }
};

const postAdd = (event, { buyNow = false } = {}) => {
    if (!canAdd.value || adding.value) {
        return;
    }

    adding.value = true;
    flyToCart(activeImage.value?.url || product.value.image_url, event);

    router.post(
        route('shop.cart.add'),
        {
            product_id: product.value.id,
            product_variant_id: selectedVariantId.value || null,
            quantity: quantity.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['shopCart', 'cartCount', 'flash'],
            onFinish: () => {
                adding.value = false;
            },
            onSuccess: () => {
                closeProduct();
                if (buyNow) {
                    openCheckout();
                }
            },
        },
    );
};

const openRelated = (slug) => {
    openProductModal(slug);
};
</script>

<template>
    <Teleport to="body">
        <div v-if="product" class="fixed inset-0 z-[76]">
            <button
                type="button"
                class="absolute inset-0 bg-brand-navy/45 backdrop-blur-[2px]"
                aria-label="Close"
                @click="closeProduct"
            />

            <aside
                data-shop-fly-root
                class="absolute inset-y-0 right-0 flex w-full max-w-2xl flex-col bg-white shadow-2xl shadow-brand-navy/20 lg:max-w-4xl"
            >
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-teal">
                            {{ hasVariants ? 'Variant product' : 'Product details' }}
                        </p>
                        <h2 class="mt-1 truncate text-lg font-semibold tracking-tight text-brand-navy sm:text-xl">
                            {{ product.name }}
                        </h2>
                        <p class="mt-0.5 text-xs text-gray-400">
                            SKU {{ selectedVariant?.sku || product.sku || '—' }}
                            <span v-if="selectedVariant?.name"> · {{ selectedVariant.name }}</span>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-brand-navy"
                        aria-label="Close details"
                        @click="closeProduct"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="grid lg:grid-cols-[1.15fr_0.95fr]">
                        <!-- Multi-image gallery -->
                        <div class="bg-gradient-to-br from-brand-navy/[0.04] via-white to-brand-orange/[0.05] p-4 sm:p-5">
                            <div class="flex gap-3">
                                <div
                                    v-if="gallery.length > 1"
                                    class="hidden max-h-[28rem] w-[4.5rem] shrink-0 flex-col gap-2 overflow-y-auto sm:flex"
                                >
                                    <button
                                        v-for="(img, index) in gallery"
                                        :key="img.id || index"
                                        type="button"
                                        class="aspect-square shrink-0 overflow-hidden rounded-xl bg-white ring-2 transition"
                                        :class="activeImageIndex === index ? 'ring-brand-orange shadow-sm' : 'ring-transparent hover:ring-gray-200'"
                                        @click="activeImageIndex = index"
                                    >
                                        <img :src="img.url" alt="" class="h-full w-full object-cover" />
                                    </button>
                                </div>

                                <div class="relative aspect-square min-w-0 flex-1 overflow-hidden rounded-2xl bg-white shadow-inner ring-1 ring-black/[0.04] sm:aspect-[4/5] lg:aspect-square">
                                    <img
                                        v-if="activeImage"
                                        :src="activeImage.url"
                                        :alt="activeImage.alt || product.name"
                                        class="h-full w-full object-cover transition duration-500"
                                    />
                                    <div v-else class="flex h-full items-center justify-center text-sm text-gray-400">
                                        No image
                                    </div>

                                    <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
                                        <span
                                            v-if="product.is_featured"
                                            class="rounded-full bg-brand-orange px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-white shadow"
                                        >
                                            Featured
                                        </span>
                                        <span
                                            v-if="gallery.length > 1"
                                            class="rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-semibold text-brand-navy shadow-sm"
                                        >
                                            {{ activeImageIndex + 1 }} / {{ gallery.length }}
                                        </span>
                                    </div>

                                    <span
                                        class="absolute right-3 top-3 rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide shadow-sm"
                                        :class="inStock ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'"
                                    >
                                        {{ inStock ? 'In stock' : 'Sold out' }}
                                    </span>

                                    <div
                                        v-if="gallery.length > 1"
                                        class="absolute inset-x-0 bottom-3 flex justify-center gap-1.5 sm:hidden"
                                    >
                                        <button
                                            v-for="(img, index) in gallery"
                                            :key="`dot-${img.id || index}`"
                                            type="button"
                                            class="h-1.5 rounded-full transition"
                                            :class="activeImageIndex === index ? 'w-5 bg-brand-orange' : 'w-1.5 bg-white/80'"
                                            @click="activeImageIndex = index"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div v-if="gallery.length > 1" class="mt-3 flex gap-2 overflow-x-auto pb-1 sm:hidden">
                                <button
                                    v-for="(img, index) in gallery"
                                    :key="`m-${img.id || index}`"
                                    type="button"
                                    class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-white ring-2"
                                    :class="activeImageIndex === index ? 'ring-brand-orange' : 'ring-transparent'"
                                    @click="activeImageIndex = index"
                                >
                                    <img :src="img.url" alt="" class="h-full w-full object-cover" />
                                </button>
                            </div>
                        </div>

                        <!-- Purchase + variants -->
                        <div class="space-y-5 p-5 sm:p-6">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        v-if="product.rating_count"
                                        type="button"
                                        class="flex items-center gap-1.5 rounded-full bg-brand-orange/10 px-2.5 py-1 text-xs text-brand-navy"
                                        @click="activeTab = 'reviews'"
                                    >
                                        <span class="font-semibold text-brand-orange">{{ product.rating_avg }}</span>
                                        <span class="text-gray-500">· {{ product.rating_count }} reviews</span>
                                    </button>
                                    <span v-else class="rounded-full bg-brand-teal/10 px-2 py-0.5 text-[11px] font-medium text-brand-teal">
                                        New arrival
                                    </span>
                                    <span v-if="stockQty != null && inStock" class="text-xs text-gray-400">
                                        {{ stockQty.toFixed(0) }} available
                                    </span>
                                </div>

                                <p class="mt-3 text-3xl font-semibold tracking-tight text-brand-navy">
                                    <template v-if="displayPrice.price != null">
                                        <span class="text-base font-medium text-gray-400">{{ displayPrice.currency }}</span>
                                        {{ Number(displayPrice.price).toFixed(2) }}
                                    </template>
                                    <template v-else>
                                        <span class="text-sm font-normal text-gray-500">{{ displayPrice.message }}</span>
                                    </template>
                                </p>
                            </div>

                            <!-- Attribute options -->
                            <div v-if="attributeGroups.length" class="space-y-4 rounded-2xl border border-gray-100 bg-gray-50/60 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Choose options</p>
                                <div v-for="group in attributeGroups" :key="group.id">
                                    <p class="text-sm font-medium text-brand-navy">
                                        {{ group.name }}
                                        <span
                                            v-if="selectedOptions[group.id]"
                                            class="ml-1 font-normal text-gray-400"
                                        >
                                            · {{ group.options.find((o) => o.id === selectedOptions[group.id])?.value }}
                                        </span>
                                    </p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <template v-if="isColorGroup(group.name)">
                                            <button
                                                v-for="opt in group.options"
                                                :key="opt.id"
                                                type="button"
                                                class="flex h-10 w-10 items-center justify-center rounded-full ring-2 transition"
                                                :class="selectedOptions[group.id] === opt.id ? 'ring-brand-orange' : 'ring-transparent hover:ring-gray-200'"
                                                :title="opt.value"
                                                @click="pickOption(group.id, opt.id)"
                                            >
                                                <span
                                                    class="h-8 w-8 rounded-full border border-black/10"
                                                    :style="looksLikeColor(opt.value) ? colorStyle(opt.value) : { background: '#e5e7eb' }"
                                                />
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button
                                                v-for="opt in group.options"
                                                :key="opt.id"
                                                type="button"
                                                class="min-w-[2.75rem] rounded-xl border px-3.5 py-2 text-sm transition"
                                                :class="selectedOptions[group.id] === opt.id
                                                    ? 'border-brand-orange bg-brand-orange text-white shadow-sm shadow-brand-orange/25'
                                                    : 'border-gray-200 bg-white text-brand-navy hover:border-brand-orange/40'"
                                                @click="pickOption(group.id, opt.id)"
                                            >
                                                {{ opt.value }}
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Variant cards when attributes missing but variants exist -->
                            <div v-else-if="hasVariants" class="space-y-3">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Select variant</p>
                                <div class="grid gap-2">
                                    <button
                                        v-for="variant in product.variants"
                                        :key="variant.id"
                                        type="button"
                                        class="flex items-center gap-3 rounded-2xl border p-2.5 text-left transition"
                                        :class="selectedVariantId === variant.id
                                            ? 'border-brand-orange bg-brand-orange/5 shadow-sm'
                                            : 'border-gray-200 hover:border-brand-orange/30'"
                                        @click="selectVariantCard(variant)"
                                    >
                                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-gray-50">
                                            <img
                                                v-if="variant.image_url"
                                                :src="variant.image_url"
                                                alt=""
                                                class="h-full w-full object-cover"
                                            />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-brand-navy">
                                                {{ variant.name || variant.sku }}
                                            </p>
                                            <p class="mt-0.5 text-xs text-gray-400">{{ variant.sku }}</p>
                                            <p v-if="variant.price != null" class="mt-1 text-sm font-semibold text-brand-orange">
                                                {{ variant.currency }} {{ Number(variant.price).toFixed(2) }}
                                            </p>
                                        </div>
                                        <span
                                            class="shrink-0 text-[10px] font-semibold uppercase"
                                            :class="variant.in_stock ? 'text-emerald-600' : 'text-red-500'"
                                        >
                                            {{ variant.in_stock ? 'In stock' : 'Out' }}
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-brand-navy">Quantity</p>
                                <div class="mt-2 inline-flex items-center rounded-2xl border border-gray-200 bg-gray-50/80 p-1">
                                    <button
                                        type="button"
                                        class="flex h-9 w-9 items-center justify-center rounded-xl text-brand-navy hover:bg-white"
                                        @click="quantity = Math.max(1, quantity - 1)"
                                    >
                                        −
                                    </button>
                                    <span class="w-10 text-center text-sm font-semibold text-brand-navy">{{ quantity }}</span>
                                    <button
                                        type="button"
                                        class="flex h-9 w-9 items-center justify-center rounded-xl text-brand-navy hover:bg-white"
                                        @click="quantity += 1"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <div class="rounded-2xl bg-brand-navy/[0.03] px-2 py-3 text-center">
                                    <p class="text-[11px] font-semibold text-brand-navy">Free ship</p>
                                    <p class="mt-0.5 text-[10px] text-gray-400">On this order</p>
                                </div>
                                <div class="rounded-2xl bg-brand-orange/[0.06] px-2 py-3 text-center">
                                    <p class="text-[11px] font-semibold text-brand-navy">Easy return</p>
                                    <p class="mt-0.5 text-[10px] text-gray-400">7 days</p>
                                </div>
                                <div class="rounded-2xl bg-brand-teal/[0.08] px-2 py-3 text-center">
                                    <p class="text-[11px] font-semibold text-brand-navy">Warranty</p>
                                    <p class="mt-0.5 text-[10px] text-gray-400">1 year</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Details + Reviews tabs -->
                    <div class="border-t border-gray-100 px-5 py-5 sm:px-6">
                        <div class="flex gap-1 rounded-2xl bg-gray-50 p-1">
                            <button
                                type="button"
                                class="flex-1 rounded-xl px-3 py-2 text-sm font-semibold transition"
                                :class="activeTab === 'details' ? 'bg-white text-brand-navy shadow-sm' : 'text-gray-500 hover:text-brand-navy'"
                                @click="activeTab = 'details'"
                            >
                                Details
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-xl px-3 py-2 text-sm font-semibold transition"
                                :class="activeTab === 'reviews' ? 'bg-white text-brand-navy shadow-sm' : 'text-gray-500 hover:text-brand-navy'"
                                @click="activeTab = 'reviews'"
                            >
                                Reviews
                                <span v-if="product.rating_count" class="ml-1 text-xs font-normal text-gray-400">
                                    ({{ product.rating_count }})
                                </span>
                            </button>
                        </div>

                        <div v-if="activeTab === 'details'" class="mt-5 space-y-4">
                            <p v-if="product.description" class="text-sm leading-relaxed text-gray-600 whitespace-pre-line">
                                {{ product.description }}
                            </p>
                            <p v-else class="text-sm text-gray-400">No detailed description for this product yet.</p>

                            <dl class="grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-2xl border border-gray-100 px-3 py-3">
                                    <dt class="text-[11px] uppercase tracking-wide text-gray-400">Type</dt>
                                    <dd class="mt-1 font-medium text-brand-navy">{{ hasVariants ? 'Variant' : 'Simple' }}</dd>
                                </div>
                                <div class="rounded-2xl border border-gray-100 px-3 py-3">
                                    <dt class="text-[11px] uppercase tracking-wide text-gray-400">SKU</dt>
                                    <dd class="mt-1 font-medium text-brand-navy">{{ selectedVariant?.sku || product.sku || '—' }}</dd>
                                </div>
                                <div v-if="hasVariants" class="rounded-2xl border border-gray-100 px-3 py-3">
                                    <dt class="text-[11px] uppercase tracking-wide text-gray-400">Variants</dt>
                                    <dd class="mt-1 font-medium text-brand-navy">{{ product.variants.length }} options</dd>
                                </div>
                                <div v-if="gallery.length" class="rounded-2xl border border-gray-100 px-3 py-3">
                                    <dt class="text-[11px] uppercase tracking-wide text-gray-400">Images</dt>
                                    <dd class="mt-1 font-medium text-brand-navy">{{ gallery.length }} photos</dd>
                                </div>
                            </dl>
                        </div>

                        <div v-else class="mt-5 space-y-4">
                            <div v-if="product.rating_count" class="flex items-center gap-3 rounded-2xl bg-brand-orange/5 px-4 py-3">
                                <p class="text-3xl font-semibold text-brand-navy">{{ product.rating_avg }}</p>
                                <div>
                                    <div class="flex text-brand-orange">
                                        <svg
                                            v-for="n in 5"
                                            :key="n"
                                            class="h-4 w-4"
                                            :class="n <= starFill ? 'fill-current' : 'fill-none text-gray-300'"
                                            viewBox="0 0 20 20"
                                            stroke="currentColor"
                                            stroke-width="1.2"
                                        >
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Based on {{ product.rating_count }} reviews</p>
                                </div>
                            </div>

                            <div v-if="reviews.length" class="space-y-3">
                                <article
                                    v-for="review in reviews"
                                    :key="review.id"
                                    class="rounded-2xl border border-gray-100 px-4 py-3"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-brand-navy">{{ review.author_name }}</p>
                                            <p v-if="review.title" class="mt-0.5 text-sm text-brand-navy/80">{{ review.title }}</p>
                                        </div>
                                        <div class="text-right">
                                            <div class="flex justify-end text-brand-orange">
                                                <svg
                                                    v-for="n in 5"
                                                    :key="n"
                                                    class="h-3.5 w-3.5"
                                                    :class="n <= review.rating ? 'fill-current' : 'fill-none text-gray-300'"
                                                    viewBox="0 0 20 20"
                                                    stroke="currentColor"
                                                    stroke-width="1.2"
                                                >
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            </div>
                                            <p class="mt-1 text-[11px] text-gray-400">{{ formatReviewDate(review.created_at) }}</p>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ review.body }}</p>
                                </article>
                            </div>
                            <p v-else class="rounded-2xl border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-gray-400">
                                No approved reviews yet. Be the first to share feedback after purchase.
                            </p>
                        </div>

                        <div v-if="(product.related || []).length" class="mt-8 border-t border-gray-100 pt-5">
                            <h3 class="text-sm font-semibold text-brand-navy">You may also like</h3>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <button
                                    v-for="item in product.related"
                                    :key="item.id"
                                    type="button"
                                    class="flex gap-2.5 rounded-2xl border border-gray-100 bg-white p-2 text-left transition hover:border-brand-teal/40 hover:shadow-sm"
                                    @click="openRelated(item.slug)"
                                >
                                    <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xl bg-gray-50">
                                        <img v-if="item.image_url" :src="item.image_url" alt="" class="h-full w-full object-cover" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="line-clamp-2 text-xs font-medium text-brand-navy">{{ item.name }}</p>
                                        <p v-if="item.price != null" class="mt-0.5 text-xs font-semibold text-brand-orange">
                                            {{ item.currency }} {{ Number(item.price).toFixed(2) }}
                                        </p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 bg-white/95 px-5 py-4 backdrop-blur sm:px-6">
                    <div class="flex gap-2.5">
                        <button
                            type="button"
                            class="flex-1 rounded-2xl border-2 border-brand-orange py-3.5 text-sm font-semibold text-brand-orange transition hover:bg-brand-orange/5 disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="!canAdd || adding"
                            @click="postAdd($event, { buyNow: false })"
                        >
                            Add to cart
                        </button>
                        <button
                            type="button"
                            class="flex-1 rounded-2xl bg-brand-orange py-3.5 text-sm font-semibold text-white shadow-lg shadow-brand-orange/25 transition hover:bg-brand-orange-dark disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="!canAdd || adding"
                            @click="postAdd($event, { buyNow: true })"
                        >
                            Buy now
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
