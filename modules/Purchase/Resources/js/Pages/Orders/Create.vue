<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    suppliers: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    productOptions: { type: Array, default: () => [] },
});

const defaultWarehouse = props.warehouses.find((w) => w.is_default)?.id ?? props.warehouses[0]?.id ?? '';

const form = useForm({
    supplier_id: props.suppliers[0]?.id ?? '',
    warehouse_id: defaultWarehouse,
    notes: '',
    status: 'pending',
    items: [{ product_id: '', product_variant_id: '', quantity: 1, unit_cost: 0 }],
});

const variantsByProduct = reactive({});

const loadVariants = async (productId, index) => {
    if (!productId) {
        variantsByProduct[index] = [];
        return;
    }
    const res = await fetch(route('purchase.orders.product-variants', productId));
    variantsByProduct[index] = await res.json();
};

const onProductChange = async (index) => {
    form.items[index].product_variant_id = '';
    await loadVariants(form.items[index].product_id, index);
};

const addLine = () => {
    form.items.push({ product_id: '', product_variant_id: '', quantity: 1, unit_cost: 0 });
};

const removeLine = (index) => {
    form.items.splice(index, 1);
    delete variantsByProduct[index];
};

const estimatedTotal = computed(() =>
    form.items.reduce((sum, line) => sum + Number(line.unit_cost || 0) * Number(line.quantity || 0), 0),
);

const submit = (status) => {
    form.status = status;
    form.post(route('purchase.orders.store'));
};
</script>

<template>
    <Head title="New Purchase Order" />

    <AdminLayout title="New Purchase Order">
        <div class="mb-4">
            <Link :href="route('purchase.orders.all')" class="text-sm text-brand-navy hover:text-brand-orange">← Orders</Link>
        </div>

        <form class="space-y-6" @submit.prevent="submit('pending')">
            <section class="admin-card grid gap-4 sm:grid-cols-2">
                <h2 class="sm:col-span-2 text-sm font-semibold text-brand-navy">Supplier & warehouse</h2>
                <div>
                    <InputLabel value="Supplier" />
                    <select v-model="form.supplier_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                        <option value="" disabled>Select supplier</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }} ({{ s.code }})</option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.supplier_id" />
                </div>
                <div>
                    <InputLabel value="Receive into warehouse" />
                    <select v-model="form.warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Default warehouse</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }} ({{ w.code }})</option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.warehouse_id" />
                </div>
                <div class="sm:col-span-2">
                    <InputLabel value="Notes" />
                    <TextInput v-model="form.notes" class="mt-1 block w-full" />
                </div>
            </section>

            <section class="admin-card space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-brand-navy">Line items</h2>
                    <button type="button" class="text-sm text-brand-orange hover:underline" @click="addLine">+ Add line</button>
                </div>

                <div v-for="(line, index) in form.items" :key="index" class="grid gap-3 rounded-lg border border-gray-100 p-3 sm:grid-cols-12">
                    <div class="sm:col-span-4">
                        <InputLabel value="Product" />
                        <select
                            v-model="line.product_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                            required
                            @change="onProductChange(index)"
                        >
                            <option value="" disabled>Select product</option>
                            <option v-for="p in productOptions" :key="p.id" :value="p.id">
                                {{ p.name }}{{ p.sku ? ` (${p.sku})` : '' }}
                            </option>
                        </select>
                        <InputError class="mt-1" :message="form.errors[`items.${index}.product_id`]" />
                    </div>
                    <div class="sm:col-span-3">
                        <InputLabel value="Variant" />
                        <select
                            v-model="line.product_variant_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                            :disabled="!(variantsByProduct[index]?.length)"
                        >
                            <option value="">None</option>
                            <option v-for="v in variantsByProduct[index] || []" :key="v.id" :value="v.id">
                                {{ v.name || v.sku }}
                            </option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Qty" />
                        <TextInput v-model="line.quantity" type="number" min="0.0001" step="any" class="mt-1 block w-full" required />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Unit cost" />
                        <TextInput v-model="line.unit_cost" type="number" min="0" step="any" class="mt-1 block w-full" required />
                    </div>
                    <div class="flex items-end sm:col-span-1">
                        <button
                            v-if="form.items.length > 1"
                            type="button"
                            class="mb-1 text-xs text-red-600 hover:underline"
                            @click="removeLine(index)"
                        >
                            Remove
                        </button>
                    </div>
                </div>

                <InputError :message="form.errors.items" />

                <p class="text-right text-sm text-gray-600">
                    Estimated total:
                    <span class="font-semibold text-brand-navy">BDT {{ estimatedTotal.toFixed(2) }}</span>
                </p>
            </section>

            <div class="flex flex-wrap justify-end gap-2">
                <SecondaryButton type="button" :disabled="form.processing" @click="submit('draft')">Save draft</SecondaryButton>
                <PrimaryButton type="submit" :disabled="form.processing">Submit for approval</PrimaryButton>
            </div>
        </form>
    </AdminLayout>
</template>
