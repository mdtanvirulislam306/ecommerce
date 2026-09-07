<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    orders: { type: Array, default: () => [] },
});

const orderOptions = computed(() =>
    props.orders.map((order) => ({
        id: order.id,
        name: `${order.number} — ${order.customer_name} (${order.currency} ${Number(order.grand_total).toFixed(2)})`,
    })),
);

const form = useForm({
    sales_order_id: '',
    due_date: '',
    notes: '',
});

const submit = () => {
    form.post(route('sales.invoices.store'));
};
</script>

<template>
    <Head title="New Invoice" />

    <AdminLayout title="New Invoice">
        <div class="mb-4">
            <Link :href="route('sales.invoices.all')" class="text-sm text-brand-navy hover:text-brand-orange">
                ← Invoices
            </Link>
        </div>

        <p class="mb-4 text-sm text-gray-500">
            Invoices snapshot lines from a confirmed sales order. One invoice per order.
        </p>

        <form class="admin-card max-w-xl space-y-4" @submit.prevent="submit">
            <div>
                <InputLabel value="Confirmed sales order" />
                <div class="mt-1">
                    <SearchableSelect
                        v-model="form.sales_order_id"
                        :options="orderOptions"
                        placeholder="Search order…"
                        :allow-clear="false"
                    />
                </div>
                <InputError class="mt-1" :message="form.errors.sales_order_id" />
                <p v-if="!orders.length" class="mt-2 text-xs text-amber-700">
                    No confirmed orders available to invoice.
                </p>
            </div>
            <div>
                <InputLabel value="Due date" />
                <TextInput v-model="form.due_date" type="date" class="mt-1 block w-full" />
            </div>
            <div>
                <InputLabel value="Notes" />
                <TextInput v-model="form.notes" class="mt-1 block w-full" />
            </div>
            <PrimaryButton :disabled="form.processing || !orders.length">Create invoice</PrimaryButton>
        </form>
    </AdminLayout>
</template>
