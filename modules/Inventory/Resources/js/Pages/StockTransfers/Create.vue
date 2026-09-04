<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    warehouses: { type: Array, default: () => [] },
    productOptions: { type: Array, default: () => [] },
});

const defaultFrom = props.warehouses.find((w) => w.is_default)?.id ?? props.warehouses[0]?.id ?? '';
const defaultTo = props.warehouses.find((w) => w.id !== defaultFrom)?.id ?? '';

const form = useForm({
    from_warehouse_id: defaultFrom,
    to_warehouse_id: defaultTo,
    notes: '',
    items: [{ product_id: '', product_variant_id: '', quantity: 1 }],
});

const variantsByProduct = reactive({});

const loadVariants = async (productId, index) => {
    if (!productId) {
        variantsByProduct[index] = [];
        return;
    }
    const res = await fetch(route('inventory.product-variants', productId));
    variantsByProduct[index] = await res.json();
};

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
    form.post(route('inventory.stock-transfer.store'));
};
</script>

<template>
    <Head title="New Stock Transfer" />

    <AdminLayout title="New Stock Transfer">
        <div class="mb-4">
            <Link :href="route('inventory.stock-transfer.index')" class="text-sm text-brand-orange hover:underline">
                ← Back to transfers
            </Link>
        </div>

        <form class="admin-card max-w-3xl space-y-5" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="From warehouse" />
                    <select v-model="form.from_warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                        <option value="">Select source…</option>
                        <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                            {{ wh.name }} ({{ wh.code }})
                        </option>
                    </select>
                    <InputError :message="form.errors.from_warehouse_id" />
                </div>
                <div>
                    <InputLabel value="To warehouse" />
                    <select v-model="form.to_warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                        <option value="">Select destination…</option>
                        <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                            {{ wh.name }} ({{ wh.code }})
                        </option>
                    </select>
                    <InputError :message="form.errors.to_warehouse_id" />
                </div>
            </div>

            <div>
                <InputLabel value="Notes" />
                <TextInput v-model="form.notes" class="mt-1 block w-full" />
                <InputError :message="form.errors.notes" />
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between">
                    <InputLabel value="Lines" />
                    <SecondaryButton type="button" @click="addLine">Add line</SecondaryButton>
                </div>
                <InputError :message="form.errors.items" />

                <div class="space-y-3">
                    <div
                        v-for="(line, index) in form.items"
                        :key="index"
                        class="grid gap-3 rounded-lg border border-gray-100 p-3 sm:grid-cols-12"
                    >
                        <div class="sm:col-span-5">
                            <select
                                v-model="line.product_id"
                                class="block w-full rounded-md border-gray-300 text-sm"
                                required
                                @change="onProductChange(index)"
                            >
                                <option value="">Product…</option>
                                <option v-for="p in productOptions" :key="p.id" :value="p.id">
                                    {{ p.name }} <template v-if="p.sku">({{ p.sku }})</template>
                                </option>
                            </select>
                            <InputError :message="form.errors[`items.${index}.product_id`]" />
                        </div>
                        <div class="sm:col-span-3">
                            <select
                                v-model="line.product_variant_id"
                                class="block w-full rounded-md border-gray-300 text-sm"
                                :disabled="!(variantsByProduct[index] || []).length"
                            >
                                <option value="">Variant (optional)</option>
                                <option v-for="v in variantsByProduct[index] || []" :key="v.id" :value="v.id">
                                    {{ v.name || v.sku }}
                                </option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <TextInput v-model="line.quantity" type="number" min="0.0001" step="any" class="block w-full" required />
                            <InputError :message="form.errors[`items.${index}.quantity`]" />
                        </div>
                        <div class="sm:col-span-2 flex items-start">
                            <SecondaryButton
                                v-if="form.items.length > 1"
                                type="button"
                                class="w-full"
                                @click="removeLine(index)"
                            >
                                Remove
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <PrimaryButton :disabled="form.processing">Transfer stock</PrimaryButton>
                <Link :href="route('inventory.stock-transfer.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
