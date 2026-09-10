<script setup>
import ShopProductCard from '../../ShopProductCard.vue';
import { computed } from 'vue';

const props = defineProps({
    widget: { type: Object, required: true },
    catalogPreview: { type: Object, default: null },
});

const products = computed(() => {
    if (props.widget.data?.products?.length) {
        return props.widget.data.products;
    }
    const limit = Number(props.widget.settings?.limit) || 8;
    return (props.catalogPreview?.products ?? []).slice(0, limit);
});

const columnsClass = computed(() => ({
    2: 'grid-cols-2',
    3: 'sm:grid-cols-3',
    4: 'sm:grid-cols-3 lg:grid-cols-4',
    5: 'sm:grid-cols-3 lg:grid-cols-5',
    6: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-6',
}[Number(props.widget.settings?.columns)] ?? 'sm:grid-cols-3 lg:grid-cols-4'));
</script>

<template>
    <div class="space-y-4">
        <h2 v-if="widget.settings?.heading" class="text-2xl font-semibold text-brand-navy">
            {{ widget.settings.heading }}
        </h2>
        <div v-if="products.length" class="grid grid-cols-2 gap-3" :class="columnsClass">
            <ShopProductCard v-for="product in products" :key="product.id" :product="product" />
        </div>
        <p v-else class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-400">
            No products to show yet.
        </p>
    </div>
</template>
