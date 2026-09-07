<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
import CustomerPicker from '@/Components/Admin/CustomerPicker.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    quotation: { type: Object, required: true },
    productOptions: { type: Array, default: () => [] },
    customers: { type: Array, default: () => [] },
    customerGroups: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
});

const productSelectOptions = computed(() =>
    props.productOptions.map((p) => ({
        id: p.id,
        name: p.sku ? `${p.name} (${p.sku})` : p.name,
    })),
);

const statusOptions = [
    { id: 'draft', name: 'Draft' },
    { id: 'sent', name: 'Sent' },
];

const form = useForm({
    customer_id: props.quotation.customer_id || '',
    customer_name: props.quotation.customer_name,
    customer_email: props.quotation.customer_email || '',
    customer_phone: props.quotation.customer_phone || '',
    customer_group_id: props.quotation.customer_group_id || '',
    warehouse_id: props.quotation.warehouse_id || '',
    notes: props.quotation.notes || '',
    valid_until: props.quotation.valid_until || '',
    status: props.quotation.status,
    items: props.quotation.items.map((item) => ({
        product_id: item.product_id || '',
        product_variant_id: item.product_variant_id || '',
        quantity: Number(item.quantity),
    })),
});

const applyCustomer = (customer) => {
    if (!customer) {
        return;
    }
    form.customer_name = customer.name;
    form.customer_email = customer.email || '';
    form.customer_phone = customer.phone || '';
    if (customer.customer_group_id) {
        form.customer_group_id = customer.customer_group_id;
    }
};

const variantsByProduct = reactive({});

const loadVariants = async (productId, index) => {
    if (!productId) {
        variantsByProduct[index] = [];
        return;
    }
    const res = await fetch(route('sales.orders.product-variants', productId));
    variantsByProduct[index] = await res.json();
};

props.quotation.items.forEach((item, index) => {
    if (item.product_id) loadVariants(item.product_id, index);
});

const onProductChange = async (index) => {
    form.items[index].product_variant_id = '';
    await loadVariants(form.items[index].product_id, index);
};

const variantOptions = (index) =>
    (variantsByProduct[index] || []).map((v) => ({ id: v.id, name: v.sku }));

const addLine = () => {
    form.items.push({ product_id: '', product_variant_id: '', quantity: 1 });
};

const removeLine = (index) => {
    form.items.splice(index, 1);
    delete variantsByProduct[index];
};

const submit = () => {
    form.put(route('sales.quotations.update', props.quotation.id));
};
</script>

<template>
    <Head :title="`Edit ${quotation.number}`" />

    <AdminLayout :title="`Edit ${quotation.number}`">
        <div class="mb-4">
            <Link :href="route('sales.quotations.show', quotation.id)" class="text-sm text-brand-navy hover:text-brand-orange">
                ← Back
            </Link>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <section class="admin-card grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <InputLabel value="CRM customer" />
                    <div class="mt-1">
                        <CustomerPicker v-model="form.customer_id" :customers="customers" @picked="applyCustomer" />
                    </div>
                    <InputError class="mt-1" :message="form.errors.customer_id" />
                </div>
                <div>
                    <InputLabel value="Customer name" />
                    <TextInput v-model="form.customer_name" class="mt-1 block w-full" :required="!form.customer_id" />
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
                    <InputLabel value="Valid until" />
                    <TextInput v-model="form.valid_until" type="date" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel value="Customer group" />
                    <div class="mt-1">
                        <SearchableSelect
                            v-model="form.customer_group_id"
                            :options="customerGroups"
                            placeholder="Default"
                            search-placeholder="Search group…"
                        />
                    </div>
                </div>
                <div>
                    <InputLabel value="Warehouse" />
                    <div class="mt-1">
                        <SearchableSelect
                            v-model="form.warehouse_id"
                            :options="warehouses"
                            placeholder="Default"
                            search-placeholder="Search warehouse…"
                        />
                    </div>
                </div>
                <div>
                    <InputLabel value="Status" />
                    <div class="mt-1">
                        <SearchableSelect
                            v-model="form.status"
                            :options="statusOptions"
                            placeholder="Status"
                            :allow-clear="false"
                        />
                    </div>
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
                <div
                    v-for="(line, index) in form.items"
                    :key="index"
                    class="grid gap-3 rounded-lg border border-gray-100 p-4 sm:grid-cols-12"
                >
                    <div class="sm:col-span-5">
                        <SearchableSelect
                            v-model="line.product_id"
                            :options="productSelectOptions"
                            placeholder="Search product…"
                            :allow-clear="false"
                            @update:model-value="onProductChange(index)"
                        />
                    </div>
                    <div class="sm:col-span-3">
                        <SearchableSelect
                            v-model="line.product_variant_id"
                            :options="variantOptions(index)"
                            placeholder="Variant"
                            search-placeholder="Search variant…"
                            :disabled="!(variantsByProduct[index] || []).length"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <TextInput v-model="line.quantity" type="number" min="0.0001" step="any" class="block w-full" />
                    </div>
                    <div class="flex items-center justify-end sm:col-span-2">
                        <button
                            v-if="form.items.length > 1"
                            type="button"
                            class="admin-data-table__action admin-data-table__action--danger"
                            title="Remove line"
                            @click="removeLine(index)"
                        >
                            <ActionIcon name="delete" />
                        </button>
                    </div>
                </div>
            </section>

            <PrimaryButton :disabled="form.processing">Save changes</PrimaryButton>
        </form>
    </AdminLayout>
</template>
