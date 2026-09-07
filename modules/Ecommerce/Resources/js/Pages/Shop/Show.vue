<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { useShopUi } from '@/Composables/useShopUi';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    product: { type: Object, required: true },
});

const { flyToCart, openCheckout, openProductModal } = useShopUi();

const quantity = ref(1);
const selectedVariantId = ref(props.product.variants?.[0]?.id ?? null);
const activeImageIndex = ref(0);
const selectedOptions = ref({});
const adding = ref(false);
const activeTab = ref('details');

const selectedVariant = computed(() =>
    props.product.variants?.find((v) => v.id === selectedVariantId.value) ?? null,
);

const gallery = computed(() => {
    if (selectedVariant.value?.media?.length) {
        return selectedVariant.value.media;
    }
    if (props.product.media?.length) {
        return props.product.media;
    }
    const fallback = selectedVariant.value?.image_url || props.product.image_url;
    return fallback ? [{ id: 0, url: fallback, alt: props.product.name }] : [];
});

const activeImage = computed(() => gallery.value[activeImageIndex.value] ?? gallery.value[0] ?? null);

const displayPrice = computed(() => {
    if (selectedVariant.value?.price != null) {
        return {
            price: selectedVariant.value.price,
            currency: selectedVariant.value.currency || props.product.currency,
            message: null,
        };
    }
    if (props.product.price != null) {
        return {
            price: props.product.price,
            currency: props.product.currency,
            message: null,
        };
    }
    return {
        price: null,
        currency: props.product.currency,
        message: selectedVariant.value?.price_message || props.product.price_message,
    };
});

const inStock = computed(() =>
    selectedVariant.value ? selectedVariant.value.in_stock : props.product.in_stock,
);

const stockQty = computed(() => {
    const qty = selectedVariant.value?.stock_available ?? props.product.stock_available;
    return qty == null ? null : Number(qty);
});

const attributeGroups = computed(() => {
    const groups = {};
    for (const variant of props.product.variants || []) {
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

const starFill = computed(() => {
    const avg = Number(props.product.rating_avg || 0);
    return Math.round(Math.min(5, Math.max(0, avg)));
});

const reviews = computed(() => props.product.reviews || []);

const hasVariants = computed(() =>
    Boolean(props.product.has_variants && (props.product.variants?.length || 0) > 0),
);

const canAdd = computed(() => {
    if (!displayPrice.value.price || !inStock.value) {
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

watch(gallery, () => {
    activeImageIndex.value = 0;
});

watch(
    () => props.product.variants,
    (variants) => {
        const first = variants?.[0];
        if (!first) {
            return;
        }
        selectedVariantId.value = first.id;
        const map = {};
        for (const attr of first.attributes || []) {
            map[attr.attribute_id] = attr.attribute_option_id;
        }
        selectedOptions.value = map;
    },
    { immediate: true },
);

const pickOption = (attributeId, optionId) => {
    selectedOptions.value = { ...selectedOptions.value, [attributeId]: optionId };
    const match = (props.product.variants || []).find((variant) =>
        (variant.attributes || []).every(
            (attr) => selectedOptions.value[attr.attribute_id] === attr.attribute_option_id,
        ),
    );
    if (match) {
        selectedVariantId.value = match.id;
    }
};

const isColorGroup = (name) => /color|colour|shade/i.test(name || '');

const looksLikeColor = (value) => {
    const v = String(value || '').trim();
    return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v)
        || /^(rgb|hsl)a?\(/i.test(v)
        || /^(red|blue|green|black|white|yellow|orange|pink|purple|gray|grey|navy|teal|brown)$/i.test(v);
};

const colorStyle = (value) => {
    const v = String(value || '').trim().toLowerCase();
    if (/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v) || /^(rgb|hsl)a?\(/i.test(v)) {
        return { background: v };
    }
    return { background: v };
};

const postAdd = (event, { buyNow = false } = {}) => {
    if (!canAdd.value || adding.value) {
        return;
    }

    adding.value = true;
    flyToCart(activeImage.value?.url || props.product.image_url, event);

    router.post(
        route('shop.cart.add'),
        {
            product_id: props.product.id,
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
                if (buyNow) {
                    openCheckout();
                }
            },
        },
    );
};
</script>

<template>
    <Head :title="product.name" />

    <StorefrontLayout dense>
        <div class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-gradient-to-b from-brand-navy/[0.05] via-brand-teal/[0.04] to-transparent" />

            <div class="relative mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
                <Link
                    :href="route('shop.index')"
                    class="inline-flex items-center gap-1.5 text-sm text-gray-500 transition hover:text-brand-navy"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to shop
                </Link>

                <div
                    data-shop-fly-root
                    class="mt-6 grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:gap-12"
                >
                    <!-- Gallery -->
                    <div>
                        <div class="flex gap-3">
                            <div v-if="gallery.length > 1" class="hidden w-20 shrink-0 flex-col gap-2 sm:flex">
                                <button
                                    v-for="(img, index) in gallery"
                                    :key="img.id || index"
                                    type="button"
                                    class="aspect-square overflow-hidden rounded-2xl bg-white ring-2 transition"
                                    :class="activeImageIndex === index ? 'ring-brand-orange shadow-sm' : 'ring-transparent hover:ring-gray-200'"
                                    @click="activeImageIndex = index"
                                >
                                    <img :src="img.url" alt="" class="h-full w-full object-cover" />
                                </button>
                            </div>

                            <div class="relative aspect-square min-w-0 flex-1 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-white via-gray-50 to-brand-teal/[0.08] shadow-sm ring-1 ring-black/[0.04]">
                                <img
                                    v-if="activeImage"
                                    :src="activeImage.url"
                                    :alt="activeImage.alt || product.name"
                                    class="h-full w-full object-cover"
                                />
                                <div v-else class="flex h-full items-center justify-center text-sm text-gray-400">
                                    No image
                                </div>
                                <span
                                    v-if="product.is_featured"
                                    class="absolute left-4 top-4 rounded-full bg-brand-orange px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-white shadow"
                                >
                                    Featured
                                </span>
                            </div>
                        </div>

                        <div v-if="gallery.length > 1" class="mt-3 flex gap-2 sm:hidden">
                            <button
                                v-for="(img, index) in gallery"
                                :key="`m-${img.id || index}`"
                                type="button"
                                class="h-16 w-16 overflow-hidden rounded-xl bg-white ring-2"
                                :class="activeImageIndex === index ? 'ring-brand-orange' : 'ring-transparent'"
                                @click="activeImageIndex = index"
                            >
                                <img :src="img.url" alt="" class="h-full w-full object-cover" />
                            </button>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="flex flex-col">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-teal">
                            Product details
                        </p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-brand-navy sm:text-4xl">
                            {{ product.name }}
                        </h1>
                        <p class="mt-1 text-sm text-gray-400">
                            SKU {{ selectedVariant?.sku || product.sku || '—' }}
                        </p>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <div v-if="product.rating_count" class="flex items-center gap-1.5">
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
                                <span class="text-sm text-gray-500">
                                    {{ product.rating_avg }} · {{ product.rating_count }} reviews
                                </span>
                            </div>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="inStock ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'"
                            >
                                {{ inStock ? 'In stock' : 'Out of stock' }}
                                <template v-if="stockQty != null && inStock"> · {{ stockQty.toFixed(0) }}</template>
                            </span>
                        </div>

                        <p class="mt-6 text-3xl font-semibold tracking-tight text-brand-navy">
                            <template v-if="displayPrice.price != null">
                                <span class="text-base font-medium text-gray-400">{{ displayPrice.currency }}</span>
                                {{ Number(displayPrice.price).toFixed(2) }}
                            </template>
                            <template v-else>
                                <span class="text-base font-normal text-gray-500">{{ displayPrice.message }}</span>
                            </template>
                        </p>

                        <div class="mt-6 grid grid-cols-3 gap-2 sm:gap-3">
                            <div class="rounded-2xl bg-brand-navy/[0.03] px-3 py-4 text-center">
                                <p class="text-xs font-semibold text-brand-navy">Free shipping</p>
                                <p class="mt-1 text-[11px] text-gray-400">On this order</p>
                            </div>
                            <div class="rounded-2xl bg-brand-orange/[0.06] px-3 py-4 text-center">
                                <p class="text-xs font-semibold text-brand-navy">Easy return</p>
                                <p class="mt-1 text-[11px] text-gray-400">7 days</p>
                            </div>
                            <div class="rounded-2xl bg-brand-teal/[0.08] px-3 py-4 text-center">
                                <p class="text-xs font-semibold text-brand-navy">Warranty</p>
                                <p class="mt-1 text-[11px] text-gray-400">1 year</p>
                            </div>
                        </div>

                        <div v-if="attributeGroups.length" class="mt-8 space-y-5 rounded-2xl border border-gray-100 bg-gray-50/60 p-4">
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
                                            class="rounded-xl border px-4 py-2 text-sm transition"
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

                        <div v-else-if="product.variants?.length" class="mt-8">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Select variant</p>
                            <div class="mt-3 grid gap-2">
                                <button
                                    v-for="variant in product.variants"
                                    :key="variant.id"
                                    type="button"
                                    class="flex items-center gap-3 rounded-2xl border p-2.5 text-left transition"
                                    :class="selectedVariantId === variant.id
                                        ? 'border-brand-orange bg-brand-orange/5'
                                        : 'border-gray-200 hover:border-brand-orange/30'"
                                    @click="selectedVariantId = variant.id"
                                >
                                    <img
                                        v-if="variant.image_url"
                                        :src="variant.image_url"
                                        alt=""
                                        class="h-14 w-14 rounded-xl object-cover"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <span class="text-sm font-medium text-brand-navy">{{ variant.name || variant.sku }}</span>
                                        <p v-if="variant.price != null" class="mt-0.5 text-sm font-semibold text-brand-orange">
                                            {{ variant.currency }} {{ Number(variant.price).toFixed(2) }}
                                        </p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div class="mt-8">
                            <p class="text-sm font-medium text-brand-navy">Quantity</p>
                            <div class="mt-2 inline-flex items-center rounded-2xl border border-gray-200 bg-white p-1">
                                <button
                                    type="button"
                                    class="flex h-10 w-10 items-center justify-center rounded-xl text-brand-navy hover:bg-gray-50"
                                    @click="quantity = Math.max(1, quantity - 1)"
                                >
                                    −
                                </button>
                                <span class="w-10 text-center text-sm font-semibold">{{ quantity }}</span>
                                <button
                                    type="button"
                                    class="flex h-10 w-10 items-center justify-center rounded-xl text-brand-navy hover:bg-gray-50"
                                    @click="quantity += 1"
                                >
                                    +
                                </button>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
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

                        <div class="mt-10 border-t border-gray-100 pt-8">
                            <div class="flex gap-1 rounded-2xl bg-gray-50 p-1">
                                <button
                                    type="button"
                                    class="flex-1 rounded-xl px-3 py-2 text-sm font-semibold transition"
                                    :class="activeTab === 'details' ? 'bg-white text-brand-navy shadow-sm' : 'text-gray-500'"
                                    @click="activeTab = 'details'"
                                >
                                    Details
                                </button>
                                <button
                                    type="button"
                                    class="flex-1 rounded-xl px-3 py-2 text-sm font-semibold transition"
                                    :class="activeTab === 'reviews' ? 'bg-white text-brand-navy shadow-sm' : 'text-gray-500'"
                                    @click="activeTab = 'reviews'"
                                >
                                    Reviews
                                    <span v-if="product.rating_count" class="text-xs font-normal text-gray-400">
                                        ({{ product.rating_count }})
                                    </span>
                                </button>
                            </div>

                            <div v-if="activeTab === 'details'" class="mt-5">
                                <p v-if="product.description" class="text-sm leading-relaxed text-gray-600 whitespace-pre-line">
                                    {{ product.description }}
                                </p>
                                <p v-else class="text-sm text-gray-400">No detailed description for this product yet.</p>
                            </div>

                            <div v-else class="mt-5 space-y-3">
                                <div v-if="reviews.length">
                                    <article
                                        v-for="review in reviews"
                                        :key="review.id"
                                        class="mb-3 rounded-2xl border border-gray-100 px-4 py-3"
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
                                    No approved reviews yet.
                                </p>
                            </div>
                        </div>

                        <div v-if="(product.related || []).length" class="mt-10 border-t border-gray-100 pt-8">
                            <h2 class="text-lg font-semibold text-brand-navy">You may also like</h2>
                            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <button
                                    v-for="item in product.related"
                                    :key="item.id"
                                    type="button"
                                    class="overflow-hidden rounded-2xl border border-gray-100 bg-white text-left transition hover:-translate-y-0.5 hover:border-brand-teal/40 hover:shadow-md"
                                    @click="openProductModal(item.slug)"
                                >
                                    <div class="aspect-square bg-gray-50">
                                        <img
                                            v-if="item.image_url"
                                            :src="item.image_url"
                                            alt=""
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                    <div class="p-2.5">
                                        <p class="line-clamp-2 text-xs font-medium text-brand-navy">{{ item.name }}</p>
                                        <p v-if="item.price != null" class="mt-1 text-xs font-semibold text-brand-orange">
                                            {{ item.currency }} {{ Number(item.price).toFixed(2) }}
                                        </p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
