<script setup>
import { useShopUi } from '@/composables/useShopUi';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const { state, closeVariant, flyToCart } = useShopUi();

const quantity = ref(1);
const selectedVariantId = ref(null);
const selectedOptions = ref({});

const product = computed(() => state.variantProduct);

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

const selectedVariant = computed(() =>
    product.value?.variants?.find((v) => v.id === selectedVariantId.value) ?? null,
);

const previewImage = computed(() =>
    selectedVariant.value?.image_url || product.value?.image_url || null,
);

const displayPrice = computed(() => {
    if (selectedVariant.value?.price != null) {
        return { price: selectedVariant.value.price, currency: selectedVariant.value.currency };
    }
    return { price: product.value?.price, currency: product.value?.currency };
});

const inStock = computed(() =>
    selectedVariant.value ? selectedVariant.value.in_stock : product.value?.in_stock,
);

watch(
    () => state.variantProduct,
    (value) => {
        quantity.value = 1;
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

const isColorGroup = (name) => /color|colour|shade/i.test(name || '');

const looksLikeColor = (value) => {
    const v = String(value || '').trim();
    return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v)
        || /^(rgb|hsl)a?\(/i.test(v)
        || /^(red|blue|green|black|white|yellow|orange|pink|purple|gray|grey|navy|teal|brown)$/i.test(v);
};

const addToCart = async (event) => {
    if (!product.value || !displayPrice.value.price || !inStock.value) {
        return;
    }
    if (product.value.has_variants && !selectedVariantId.value) {
        return;
    }

    flyToCart(previewImage.value, event);
    closeVariant();

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
        },
    );
};
</script>

<template>
    <Teleport to="body">
        <div v-if="product" class="fixed inset-0 z-[78]">
            <button type="button" class="absolute inset-0 bg-brand-navy/40" aria-label="Close" @click="closeVariant" />
            <aside data-shop-fly-root class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-white shadow-2xl">
                <div class="flex items-start gap-4 border-b border-gray-100 p-5">
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl bg-gray-50">
                        <img v-if="previewImage" :src="previewImage" alt="" class="h-full w-full object-cover" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-base font-semibold text-brand-navy">{{ product.name }}</h3>
                            <button type="button" class="rounded-full p-1 text-gray-400 hover:bg-gray-50" @click="closeVariant">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-lg font-semibold text-brand-navy">
                            <template v-if="displayPrice.price != null">
                                {{ displayPrice.currency }} {{ Number(displayPrice.price).toFixed(2) }}
                            </template>
                        </p>
                    </div>
                </div>

                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    <div v-for="group in attributeGroups" :key="group.id">
                        <p class="text-sm font-medium text-brand-navy">{{ group.name }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="opt in group.options"
                                :key="opt.id"
                                type="button"
                                class="transition"
                                :class="isColorGroup(group.name) && looksLikeColor(opt.value)
                                    ? `h-8 w-8 rounded-full ring-2 ${selectedOptions[group.id] === opt.id ? 'ring-brand-orange' : 'ring-gray-200'}`
                                    : `min-w-10 rounded-lg border px-3 py-1.5 text-sm ${selectedOptions[group.id] === opt.id ? 'border-brand-orange bg-brand-orange text-white' : 'border-gray-200 text-brand-navy'}`"
                                :style="isColorGroup(group.name) && looksLikeColor(opt.value) ? { backgroundColor: opt.value } : undefined"
                                :title="opt.value"
                                @click="pickOption(group.id, opt.id)"
                            >
                                <span v-if="!(isColorGroup(group.name) && looksLikeColor(opt.value))">{{ opt.value }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="!attributeGroups.length && product.variants?.length">
                        <p class="text-sm font-medium text-brand-navy">Options</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="variant in product.variants"
                                :key="variant.id"
                                type="button"
                                class="rounded-lg border px-3 py-1.5 text-sm"
                                :class="selectedVariantId === variant.id ? 'border-brand-orange bg-brand-orange/10 text-brand-navy' : 'border-gray-200'"
                                @click="selectedVariantId = variant.id"
                            >
                                {{ variant.name || variant.sku }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-brand-navy">Quantity</p>
                        <div class="mt-2 inline-flex items-center gap-2 rounded-xl border border-gray-200 px-2 py-1">
                            <button type="button" class="h-8 w-8 text-lg" @click="quantity = Math.max(1, quantity - 1)">−</button>
                            <span class="w-8 text-center text-sm font-medium">{{ quantity }}</span>
                            <button type="button" class="h-8 w-8 text-lg" @click="quantity += 1">+</button>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 px-5 py-4">
                    <button
                        type="button"
                        class="w-full rounded-xl bg-brand-orange py-3 text-sm font-semibold text-white disabled:opacity-40"
                        :disabled="!displayPrice.price || !inStock"
                        @click="addToCart"
                    >
                        Add to Cart
                    </button>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
