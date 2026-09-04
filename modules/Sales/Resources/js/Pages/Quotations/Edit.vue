<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    quotation: { type: Object, required: true },
    productOptions: { type: Array, default: () => [] },
    customerGroups: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
});

const form = useForm({
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
                    <InputLabel value="Valid until" />
                    <TextInput v-model="form.valid_until" type="date" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel value="Customer group" />
                    <select v-model="form.customer_group_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Default</option>
                        <option v-for="g in customerGroups" :key="g.id" :value="g.id">{{ g.name }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Warehouse" />
                    <select v-model="form.warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Default</option>
                        <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Status" />
                    <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
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
                <div
                    v-for="(line, index) in form.items"
                    :key="index"
                    class="grid gap-3 rounded-lg border border-gray-100 p-4 sm:grid-cols-12"
                >
                    <div class="sm:col-span-5">
                        <select
                            v-model="line.product_id"
                            class="block w-full rounded-md border-gray-300 text-sm"
                            required
                            @change="onProductChange(index)"
                        >
                            <option value="">Product…</option>
                            <option v-for="p in productOptions" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <select
                            v-model="line.product_variant_id"
                            class="block w-full rounded-md border-gray-300 text-sm"
                            :disabled="!(variantsByProduct[index] || []).length"
                        >
                            <option value="">Variant</option>
                            <option v-for="v in variantsByProduct[index] || []" :key="v.id" :value="v.id">{{ v.sku }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <TextInput v-model="line.quantity" type="number" min="0.0001" step="any" class="block w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <SecondaryButton v-if="form.items.length > 1" type="button" @click="removeLine(index)">
                            Remove
                        </SecondaryButton>
                    </div>
                </div>
            </section>

            <PrimaryButton :disabled="form.processing">Save changes</PrimaryButton>
        </form>
    </AdminLayout>
</template>
