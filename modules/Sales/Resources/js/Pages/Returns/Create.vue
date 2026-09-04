<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    invoices: { type: Array, default: () => [] },
    orders: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    productOptions: { type: Array, default: () => [] },
});

const defaultWarehouse = props.warehouses.find((w) => w.is_default)?.id ?? props.warehouses[0]?.id ?? '';

const form = useForm({
    sales_invoice_id: '',
    sales_order_id: '',
    warehouse_id: defaultWarehouse,
    customer_name: '',
    notes: '',
    confirm: true,
    items: [{ product_id: '', name: '', sku: '', quantity: 1, unit_price: 0 }],
});

const onProductChange = (index) => {
    const product = props.productOptions.find((p) => String(p.id) === String(form.items[index].product_id));
    if (product) {
        form.items[index].name = product.name;
        form.items[index].sku = product.sku || '';
    }
};

const addLine = () => {
    form.items.push({ product_id: '', name: '', sku: '', quantity: 1, unit_price: 0 });
};

const removeLine = (index) => {
    form.items.splice(index, 1);
};

const submit = (confirm) => {
    form.confirm = confirm;
    form.post(route('sales.returns.store'));
};
</script>

<template>
    <Head title="New Sales Return" />

    <AdminLayout title="New Sales Return">
        <div class="mb-4">
            <Link :href="route('sales.returns.index')" class="text-sm text-brand-navy hover:text-brand-orange">
                ← Returns
            </Link>
        </div>

        <form class="space-y-6" @submit.prevent="submit(true)">
            <section class="admin-card grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="Invoice (optional if order set)" />
                    <select v-model="form.sales_invoice_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">None</option>
                        <option v-for="inv in invoices" :key="inv.id" :value="inv.id">
                            {{ inv.number }} — {{ inv.customer_name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.sales_invoice_id" />
                </div>
                <div>
                    <InputLabel value="Sales order (optional if invoice set)" />
                    <select v-model="form.sales_order_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">None</option>
                        <option v-for="order in orders" :key="order.id" :value="order.id">
                            {{ order.number }} — {{ order.customer_name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.sales_order_id" />
                </div>
                <div>
                    <InputLabel value="Warehouse (restock)" />
                    <select v-model="form.warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Default</option>
                        <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Customer name" />
                    <TextInput v-model="form.customer_name" class="mt-1 block w-full" />
                </div>
                <div class="sm:col-span-2">
                    <InputLabel value="Notes" />
                    <TextInput v-model="form.notes" class="mt-1 block w-full" />
                </div>
            </section>

            <section class="admin-card space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-brand-navy">Return lines</h2>
                    <SecondaryButton type="button" @click="addLine">Add line</SecondaryButton>
                </div>
                <InputError :message="form.errors.items" />
                <div
                    v-for="(line, index) in form.items"
                    :key="index"
                    class="grid gap-3 rounded-lg border border-gray-100 p-4 sm:grid-cols-12"
                >
                    <div class="sm:col-span-4">
                        <select
                            v-model="line.product_id"
                            class="block w-full rounded-md border-gray-300 text-sm"
                            @change="onProductChange(index)"
                        >
                            <option value="">Product (optional)</option>
                            <option v-for="p in productOptions" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <TextInput v-model="line.name" placeholder="Name" class="block w-full" required />
                    </div>
                    <div class="sm:col-span-2">
                        <TextInput v-model="line.quantity" type="number" min="0.0001" step="any" class="block w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <TextInput v-model="line.unit_price" type="number" min="0" step="0.01" class="block w-full" />
                    </div>
                    <div class="sm:col-span-1">
                        <SecondaryButton v-if="form.items.length > 1" type="button" @click="removeLine(index)">
                            ×
                        </SecondaryButton>
                    </div>
                </div>
            </section>

            <div class="flex gap-2">
                <PrimaryButton type="button" :disabled="form.processing" @click="submit(true)">
                    Create & confirm (restock)
                </PrimaryButton>
                <SecondaryButton type="button" :disabled="form.processing" @click="submit(false)">
                    Save draft
                </SecondaryButton>
            </div>
        </form>
    </AdminLayout>
</template>
