<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    productOptions: { type: Array, default: () => [] },
    customerGroups: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
});

const defaultWarehouse = props.warehouses.find((w) => w.is_default)?.id ?? props.warehouses[0]?.id ?? '';

const form = useForm({
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    customer_group_id: '',
    warehouse_id: defaultWarehouse,
    notes: '',
    status: 'pending',
    items: [{ product_id: '', product_variant_id: '', quantity: 1 }],
});

const lineMeta = reactive({});
const variantsByProduct = reactive({});

const loadVariants = async (productId, index) => {
    if (!productId) {
        variantsByProduct[index] = [];
        return;
    }
    const res = await fetch(route('sales.orders.product-variants', productId));
    variantsByProduct[index] = await res.json();
};

const previewLine = async (index) => {
    const line = form.items[index];
    if (!line.product_id) {
        delete lineMeta[index];
        return;
    }

    const params = new URLSearchParams({
        product_id: line.product_id,
        quantity: String(line.quantity || 1),
    });
    if (line.product_variant_id) params.set('product_variant_id', line.product_variant_id);
    if (form.customer_group_id) params.set('customer_group_id', form.customer_group_id);

    const res = await fetch(`${route('sales.orders.preview-price')}?${params}`);
    lineMeta[index] = await res.json();
};

watch(
    () => form.customer_group_id,
    () => {
        form.items.forEach((_, index) => previewLine(index));
    },
);

const onProductChange = async (index) => {
    form.items[index].product_variant_id = '';
    await loadVariants(form.items[index].product_id, index);
    await previewLine(index);
};

const addLine = () => {
    form.items.push({ product_id: '', product_variant_id: '', quantity: 1 });
};

const removeLine = (index) => {
    form.items.splice(index, 1);
    delete lineMeta[index];
    delete variantsByProduct[index];
};

const estimatedTotal = computed(() =>
    form.items.reduce((sum, line, index) => {
        const price = lineMeta[index]?.resolved ? Number(lineMeta[index].price) : 0;
        return sum + price * Number(line.quantity || 0);
    }, 0),
);

const submit = (status) => {
    form.status = status;
    form.post(route('sales.orders.store'));
};
</script>

<template>
    <Head title="New Sales Order" />

    <AdminLayout title="New Sales Order">
        <div class="mb-4">
            <Link :href="route('sales.orders.all')" class="text-sm text-brand-navy hover:text-brand-orange">← Orders</Link>
        </div>

        <form class="space-y-6" @submit.prevent="submit('pending')">
            <section class="admin-card grid gap-4 sm:grid-cols-2">
                <h2 class="sm:col-span-2 text-sm font-semibold text-brand-navy">Customer</h2>
                <div>
                    <InputLabel value="Customer name" />
                    <TextInput v-model="form.customer_name" class="mt-1 block w-full" required />
                    <InputError class="mt-1" :message="form.errors.customer_name" />
                </div>
                <div>
                    <InputLabel value="Email" />
                    <TextInput v-model="form.customer_email" type="email" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel value="Phone" />
                    <TextInput v-model="form.customer_phone" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel value="Customer group (pricing)" />
                    <select v-model="form.customer_group_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Default price list</option>
                        <option v-for="g in customerGroups" :key="g.id" :value="g.id">{{ g.name }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Warehouse" />
                    <select v-model="form.warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Default warehouse</option>
                        <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <InputLabel value="Notes" />
                    <TextInput v-model="form.notes" class="mt-1 block w-full" />
                </div>
            </section>

            <section class="admin-card space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-brand-navy">Line items</h2>
                    <SecondaryButton type="button" @click="addLine">Add line</SecondaryButton>
                </div>
                <InputError :message="form.errors.items" />

                <div
                    v-for="(line, index) in form.items"
                    :key="index"
                    class="grid gap-3 rounded-lg border border-gray-100 p-4 sm:grid-cols-12"
                >
                    <div class="sm:col-span-5">
                        <InputLabel value="Product" />
                        <select
                            v-model="line.product_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                            required
                            @change="onProductChange(index)"
                        >
                            <option value="">Select…</option>
                            <option v-for="p in productOptions" :key="p.id" :value="p.id">
                                {{ p.name }} {{ p.sku ? `(${p.sku})` : '' }}
                            </option>
                        </select>
                    </div>
                    <div v-if="variantsByProduct[index]?.length" class="sm:col-span-3">
                        <InputLabel value="Variant" />
                        <select
                            v-model="line.product_variant_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                            @change="previewLine(index)"
                        >
                            <option value="">Default</option>
                            <option v-for="v in variantsByProduct[index]" :key="v.id" :value="v.id">{{ v.sku }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Qty" />
                        <TextInput
                            v-model="line.quantity"
                            type="number"
                            min="0.0001"
                            step="0.0001"
                            class="mt-1 block w-full"
                            @change="previewLine(index)"
                        />
                    </div>
                    <div class="sm:col-span-2 flex flex-col justify-end">
                        <p v-if="lineMeta[index]?.resolved" class="text-sm font-medium text-brand-navy">
                            {{ lineMeta[index].currency }} {{ Number(lineMeta[index].price).toFixed(2) }}
                            <span class="block text-xs font-normal text-gray-500">
                                {{ lineMeta[index].price_list_name }}
                            </span>
                        </p>
                        <p v-else-if="lineMeta[index]" class="text-xs text-amber-700">
                            {{ lineMeta[index].message || 'No price' }}
                        </p>
                        <button
                            v-if="form.items.length > 1"
                            type="button"
                            class="mt-1 text-left text-xs text-red-600"
                            @click="removeLine(index)"
                        >
                            Remove
                        </button>
                    </div>
                </div>

                <p class="text-right text-sm font-semibold text-brand-navy">
                    Estimated total: BDT {{ estimatedTotal.toFixed(2) }}
                </p>
            </section>

            <div class="flex flex-wrap gap-2">
                <PrimaryButton type="button" :disabled="form.processing" @click="submit('confirmed')">
                    Create & confirm (fulfill stock)
                </PrimaryButton>
                <SecondaryButton type="button" :disabled="form.processing" @click="submit('pending')">
                    Save as pending
                </SecondaryButton>
                <SecondaryButton type="button" :disabled="form.processing" @click="submit('draft')">
                    Save draft
                </SecondaryButton>
            </div>
        </form>
    </AdminLayout>
</template>
