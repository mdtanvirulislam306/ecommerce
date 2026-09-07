<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    product: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const selectedVariantId = ref(props.product.variants?.[0]?.id ?? null);
const activeImageIndex = ref(0);

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
    if (selectedVariant.value?.price) {
        return {
            price: selectedVariant.value.price,
            currency: selectedVariant.value.currency || props.product.currency,
            message: null,
        };
    }
    if (props.product.price) {
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

const stockLabel = computed(() => {
    const qty = selectedVariant.value
        ? selectedVariant.value.stock_available
        : props.product.stock_available;
    return inStock.value ? `In stock (${Number(qty).toFixed(0)})` : 'Out of stock';
});

watch(gallery, () => {
    activeImageIndex.value = 0;
});

watch(selectedVariantId, () => {
    activeImageIndex.value = 0;
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

const selectedOptions = ref({});

watch(
    () => props.product.variants,
    (variants) => {
        const first = variants?.[0];
        if (!first) return;
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

const addToCart = () => {
    router.post(
        route('shop.cart.add'),
        {
            product_id: props.product.id,
            product_variant_id: selectedVariantId.value || null,
            quantity: 1,
        },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head :title="product.name" />

    <StorefrontLayout>
        <div v-if="flash?.success" class="mb-4 text-sm text-emerald-700">{{ flash.success }}</div>

        <Link :href="route('shop.index')" class="text-sm text-gray-500 hover:text-brand-navy">← Back to shop</Link>

        <div class="mt-6 grid gap-10 lg:grid-cols-2">
            <div>
                <div class="aspect-square overflow-hidden rounded-2xl bg-gray-50 ring-1 ring-black/5">
                    <img
                        v-if="activeImage"
                        :src="activeImage.url"
                        :alt="activeImage.alt || product.name"
                        class="h-full w-full object-cover transition duration-300"
                    />
                    <div v-else class="flex h-full items-center justify-center text-sm text-gray-400">No image</div>
                </div>
                <div v-if="gallery.length > 1" class="mt-3 flex flex-wrap gap-2">
                    <button
                        v-for="(img, index) in gallery"
                        :key="img.id || index"
                        type="button"
                        class="h-16 w-16 overflow-hidden rounded-lg ring-2 transition"
                        :class="activeImageIndex === index ? 'ring-brand-navy' : 'ring-transparent hover:ring-gray-300'"
                        @click="activeImageIndex = index"
                    >
                        <img :src="img.url" alt="" class="h-full w-full object-cover" />
                    </button>
                </div>
            </div>

            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-brand-navy">{{ product.name }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ selectedVariant?.sku || product.sku || 'No SKU' }}
                </p>

                <p class="mt-6 text-2xl font-semibold text-brand-navy">
                    <template v-if="displayPrice.price">
                        {{ displayPrice.currency }} {{ Number(displayPrice.price).toFixed(2) }}
                    </template>
                    <template v-else>
                        <span class="text-base font-normal text-gray-500">{{ displayPrice.message }}</span>
                    </template>
                </p>
                <p class="mt-1 text-sm" :class="inStock ? 'text-emerald-700' : 'text-red-600'">
                    {{ stockLabel }}
                </p>

                <p v-if="product.description" class="mt-6 text-sm leading-relaxed text-gray-600">
                    {{ product.description }}
                </p>

                <div v-if="attributeGroups.length" class="mt-8 space-y-5">
                    <div v-for="group in attributeGroups" :key="group.id">
                        <p class="text-sm font-medium text-brand-navy">{{ group.name }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="opt in group.options"
                                :key="opt.id"
                                type="button"
                                class="rounded-full border px-3 py-1.5 text-sm transition"
                                :class="
                                    selectedOptions[group.id] === opt.id
                                        ? 'border-brand-navy bg-brand-navy text-white'
                                        : 'border-gray-200 bg-white text-brand-navy hover:border-brand-navy/40'
                                "
                                @click="pickOption(group.id, opt.id)"
                            >
                                {{ opt.value }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else-if="product.variants?.length" class="mt-8">
                    <p class="text-sm font-medium text-brand-navy">Variant</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="variant in product.variants"
                            :key="variant.id"
                            type="button"
                            class="flex items-center gap-2 rounded-xl border px-3 py-2 text-sm transition"
                            :class="
                                selectedVariantId === variant.id
                                    ? 'border-brand-navy bg-brand-navy/5'
                                    : 'border-gray-200 hover:border-brand-navy/30'
                            "
                            @click="selectedVariantId = variant.id"
                        >
                            <img
                                v-if="variant.image_url"
                                :src="variant.image_url"
                                alt=""
                                class="h-8 w-8 rounded object-cover"
                            />
                            <span>{{ variant.name || variant.sku }}</span>
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    class="mt-8 inline-flex rounded-xl bg-brand-navy px-5 py-3 text-sm font-medium text-white transition hover:bg-brand-navy/90 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="!displayPrice.price || !inStock"
                    @click="addToCart"
                >
                    Add to cart
                </button>
            </div>
        </div>
    </StorefrontLayout>
</template>
