<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    warehouses: { type: Array, default: () => [] },
    productOptions: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash);

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
    form.post(route('inventory.stock-transfer.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="New Stock Transfer" />

    <AdminLayout title="New Stock Transfer">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="max-w-xl text-sm text-gray-500">
                Move stock between warehouses. Each transfer writes transfer_out and transfer_in movements.
            </p>
            <Link
                :href="route('inventory.stock-transfer.index')"
                class="text-sm font-medium text-brand-navy hover:text-brand-orange"
            >
                ← Back to transfers
            </Link>
        </div>

        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>
        <div v-if="flash?.error" class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ flash.error }}
        </div>

        <form class="admin-card max-w-3xl space-y-5" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="From warehouse" />
                    <select v-model="form.from_warehouse_id" class="admin-filter-select mt-1.5 block w-full" required>
                        <option value="">Select source…</option>
                        <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                            {{ wh.name }} ({{ wh.code }})
                        </option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.from_warehouse_id" />
                </div>
                <div>
                    <InputLabel value="To warehouse" />
                    <select v-model="form.to_warehouse_id" class="admin-filter-select mt-1.5 block w-full" required>
                        <option value="">Select destination…</option>
                        <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                            {{ wh.name }} ({{ wh.code }})
                        </option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.to_warehouse_id" />
                </div>
            </div>

            <div>
                <InputLabel value="Notes" />
                <TextInput v-model="form.notes" class="mt-1.5 block w-full" />
                <InputError class="mt-1" :message="form.errors.notes" />
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <InputLabel value="Lines" />
                    <SecondaryButton type="button" @click="addLine">Add line</SecondaryButton>
                </div>
                <InputError :message="form.errors.items" />

                <div class="space-y-3">
                    <div
                        v-for="(line, index) in form.items"
                        :key="index"
                        class="grid gap-3 rounded-xl border border-gray-100 bg-gray-50/50 p-3 sm:grid-cols-12"
                    >
                        <div class="sm:col-span-5">
                            <select
                                v-model="line.product_id"
                                class="admin-filter-select block w-full"
                                required
                                @change="onProductChange(index)"
                            >
                                <option value="">Product…</option>
                                <option v-for="p in productOptions" :key="p.id" :value="p.id">
                                    {{ p.name }} <template v-if="p.sku">({{ p.sku }})</template>
                                </option>
                            </select>
                            <InputError class="mt-1" :message="form.errors[`items.${index}.product_id`]" />
                        </div>
                        <div class="sm:col-span-3">
                            <select
                                v-model="line.product_variant_id"
                                class="admin-filter-select block w-full"
                                :disabled="!(variantsByProduct[index] || []).length"
                            >
                                <option value="">Variant (optional)</option>
                                <option v-for="v in variantsByProduct[index] || []" :key="v.id" :value="v.id">
                                    {{ v.name || v.sku }}
                                </option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <TextInput
                                v-model="line.quantity"
                                type="number"
                                min="0.0001"
                                step="any"
                                class="block w-full"
                                required
                            />
                            <InputError class="mt-1" :message="form.errors[`items.${index}.quantity`]" />
                        </div>
                        <div class="flex items-start justify-end sm:col-span-2">
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
                </div>
            </div>

            <div class="flex flex-wrap gap-3 border-t border-gray-100 pt-4">
                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? 'Transferring…' : 'Transfer stock' }}
                </PrimaryButton>
                <Link :href="route('inventory.stock-transfer.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
