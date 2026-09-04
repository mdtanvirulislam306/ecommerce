<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    customerGroups: { type: Array, default: () => [] },
    priceLists: { type: Array, default: () => [] },
    productOptions: { type: Array, default: () => [] },
    form: {
        type: Object,
        default: () => ({
            product_id: '',
            product_variant_id: '',
            quantity: 1,
            customer_group_id: '',
            price_list_id: '',
        }),
    },
    preview: { type: Object, default: null },
});

const productId = ref(props.form.product_id ?? '');
const variantId = ref(props.form.product_variant_id ?? '');
const quantity = ref(props.form.quantity ?? 1);
const customerGroupId = ref(props.form.customer_group_id ?? '');
const priceListId = ref(props.form.price_list_id ?? '');
const variants = ref([]);
const loadingVariants = ref(false);

const loadVariants = async (id) => {
    if (!id) {
        variants.value = [];
        variantId.value = '';
        return;
    }
    loadingVariants.value = true;
    try {
        const res = await fetch(route('commerce.pricing.product-variants', id));
        variants.value = await res.json();
    } finally {
        loadingVariants.value = false;
    }
};

watch(
    () => props.form.product_id,
    (id) => {
        productId.value = id ?? '';
        loadVariants(productId.value);
    },
    { immediate: true },
);

watch(productId, (id) => {
    variantId.value = '';
    loadVariants(id);
});

const calculate = () => {
    router.get(
        route('commerce.pricing.quantity.index'),
        {
            product_id: productId.value || undefined,
            product_variant_id: variantId.value || undefined,
            quantity: quantity.value,
            customer_group_id: customerGroupId.value || undefined,
            price_list_id: priceListId.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Price Calculator" />

    <AdminLayout title="Quantity / Tier Pricing">
        <p class="mb-4 text-sm text-gray-500">
            Resolve unit price from customer group → price list → quantity tier. Used at cart and order time.
        </p>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">Calculate price</h2>

                <div>
                    <InputLabel value="Product" />
                    <select
                        v-model="productId"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                    >
                        <option value="">Select product…</option>
                        <option v-for="p in productOptions" :key="p.id" :value="p.id">
                            {{ p.name }} {{ p.sku ? `(${p.sku})` : '' }}
                        </option>
                    </select>
                </div>

                <div v-if="variants.length">
                    <InputLabel value="Variant SKU" />
                    <select
                        v-model="variantId"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                    >
                        <option value="">Simple / default SKU</option>
                        <option v-for="v in variants" :key="v.id" :value="v.id">
                            {{ v.sku }}
                        </option>
                    </select>
                </div>

                <div>
                    <InputLabel value="Quantity" />
                    <TextInput v-model="quantity" type="number" min="1" class="mt-1 block w-full" />
                </div>

                <div>
                    <InputLabel value="Customer group" />
                    <select
                        v-model="customerGroupId"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                    >
                        <option value="">Use default price list</option>
                        <option v-for="g in customerGroups" :key="g.id" :value="g.id">
                            {{ g.name }}
                            <template v-if="g.price_list"> → {{ g.price_list.name }}</template>
                        </option>
                    </select>
                </div>

                <div>
                    <InputLabel value="Or pick price list directly" />
                    <select
                        v-model="priceListId"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                    >
                        <option value="">From customer group / default</option>
                        <option v-for="list in priceLists" :key="list.id" :value="list.id">
                            {{ list.name }} ({{ list.code }})
                        </option>
                    </select>
                </div>

                <PrimaryButton type="button" @click="calculate">Resolve price</PrimaryButton>
            </section>

            <section class="admin-card">
                <h2 class="text-sm font-semibold text-brand-navy">Result</h2>

                <div v-if="!preview" class="mt-4 text-sm text-gray-500">
                    Select a product and click Resolve price.
                </div>

                <div v-else-if="preview.resolved" class="mt-4 space-y-3">
                    <p class="text-3xl font-semibold text-brand-navy">
                        {{ preview.currency }} {{ Number(preview.price).toFixed(2) }}
                        <span class="text-base font-normal text-gray-500">/ unit</span>
                    </p>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Price list</dt>
                            <dd class="font-medium text-brand-navy">{{ preview.price_list_name }}</dd>
                        </div>
                        <div v-if="preview.customer_group_name" class="flex justify-between gap-4">
                            <dt class="text-gray-500">Customer group</dt>
                            <dd>{{ preview.customer_group_name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Tier min quantity</dt>
                            <dd>{{ preview.min_quantity }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-gray-100 pt-2">
                            <dt class="text-gray-500">Line total ({{ quantity }} pcs)</dt>
                            <dd class="font-semibold text-brand-navy">
                                {{ preview.currency }}
                                {{ (Number(preview.price) * Number(quantity)).toFixed(2) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div v-else class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ preview.message || 'Could not resolve a price.' }}
                    <p v-if="preview.price_list_name" class="mt-1 text-xs text-amber-700">
                        List checked: {{ preview.price_list_name }}
                    </p>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
