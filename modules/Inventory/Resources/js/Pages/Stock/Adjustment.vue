<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    warehouses: { type: Array, default: () => [] },
    productOptions: { type: Array, default: () => [] },
});

const variants = ref([]);
const loadingVariants = ref(false);

const defaultWarehouseId = props.warehouses.find((w) => w.is_default)?.id
    ?? props.warehouses[0]?.id
    ?? '';

const form = useForm({
    warehouse_id: defaultWarehouseId,
    product_id: '',
    product_variant_id: '',
    direction: 'in',
    quantity: 1,
    note: '',
    reorder_point: '',
});

const loadVariants = async (productId) => {
    if (!productId) {
        variants.value = [];
        form.product_variant_id = '';
        return;
    }

    loadingVariants.value = true;
    try {
        const res = await fetch(route('inventory.product-variants', productId));
        variants.value = await res.json();
    } finally {
        loadingVariants.value = false;
    }
};

watch(
    () => form.product_id,
    (id) => {
        form.product_variant_id = '';
        loadVariants(id);
    },
);

const submit = () => {
    form.post(route('inventory.stock-adjustment.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Stock Adjustment" />

    <AdminLayout title="Stock Adjustment">
        <p class="mb-4 text-sm text-gray-500">
            Manual inbound/outbound adjustments write a stock movement and update on-hand quantity.
        </p>

        <form class="admin-card max-w-xl space-y-4" @submit.prevent="submit">
            <div>
                <InputLabel value="Warehouse" />
                <select v-model="form.warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                    <option value="">Select warehouse…</option>
                    <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                        {{ wh.name }} ({{ wh.code }})
                    </option>
                </select>
                <InputError class="mt-1" :message="form.errors.warehouse_id" />
            </div>

            <div>
                <InputLabel value="Product" />
                <select v-model="form.product_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                    <option value="">Select product…</option>
                    <option v-for="p in productOptions" :key="p.id" :value="p.id">
                        {{ p.name }} {{ p.sku ? `(${p.sku})` : '' }}
                    </option>
                </select>
                <InputError class="mt-1" :message="form.errors.product_id" />
            </div>

            <div v-if="variants.length">
                <InputLabel value="Variant SKU" />
                <select v-model="form.product_variant_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    <option value="">Simple / default SKU</option>
                    <option v-for="v in variants" :key="v.id" :value="v.id">
                        {{ v.sku }} {{ v.name ? `— ${v.name}` : '' }}
                    </option>
                </select>
            </div>

            <div>
                <InputLabel value="Direction" />
                <select v-model="form.direction" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    <option value="in">Adjustment in (+)</option>
                    <option value="out">Adjustment out (−)</option>
                </select>
            </div>

            <div>
                <InputLabel value="Quantity" />
                <TextInput v-model="form.quantity" type="number" min="0.0001" step="0.0001" class="mt-1 block w-full" required />
                <InputError class="mt-1" :message="form.errors.quantity" />
            </div>

            <div>
                <InputLabel value="Reorder point (optional)" />
                <TextInput v-model="form.reorder_point" type="number" min="0" step="1" class="mt-1 block w-full" />
            </div>

            <div>
                <InputLabel value="Note" />
                <TextInput v-model="form.note" class="mt-1 block w-full" placeholder="Opening stock, count correction…" />
            </div>

            <div class="flex flex-wrap gap-2">
                <PrimaryButton type="submit" :disabled="form.processing">Record adjustment</PrimaryButton>
                <Link :href="route('inventory.stock-overview.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
