<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    priceList: { type: Object, required: true },
    productOptions: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const deleteItemTarget = ref(null);
const editItemTarget = ref(null);
const variants = ref([]);
const loadingVariants = ref(false);

const metaForm = useForm({
    name: props.priceList.name,
    code: props.priceList.code,
    description: props.priceList.description || '',
    currency: props.priceList.currency,
    is_active: props.priceList.is_active,
    is_default: props.priceList.is_default,
    sort_order: props.priceList.sort_order,
});

const itemForm = useForm({
    product_id: '',
    product_variant_id: '',
    price: '',
    min_quantity: 1,
});

const editItemForm = useForm({
    price: '',
    min_quantity: 1,
});

const deleteItemForm = useForm({});

const saveMeta = () => {
    metaForm.put(route('commerce.pricing.price-lists.update', props.priceList.id), {
        preserveScroll: true,
    });
};

const addItem = () => {
    itemForm.post(route('commerce.pricing.price-lists.items.store', props.priceList.id), {
        preserveScroll: true,
        onSuccess: () => {
            itemForm.reset();
            itemForm.min_quantity = 1;
            variants.value = [];
        },
    });
};

const confirmDeleteItem = () => {
    if (!deleteItemTarget.value) return;
    deleteItemForm.delete(
        route('commerce.pricing.price-lists.items.destroy', [props.priceList.id, deleteItemTarget.value.id]),
        {
            preserveScroll: true,
            onSuccess: () => {
                deleteItemTarget.value = null;
            },
        },
    );
};

const openEditItem = (item) => {
    editItemTarget.value = item;
    editItemForm.price = item.price;
    editItemForm.min_quantity = item.min_quantity;
    editItemForm.clearErrors();
};

const closeEditItem = () => {
    if (!editItemForm.processing) {
        editItemTarget.value = null;
    }
};

const saveEditItem = () => {
    if (!editItemTarget.value) return;

    editItemForm.put(
        route('commerce.pricing.price-lists.items.update', [props.priceList.id, editItemTarget.value.id]),
        {
            preserveScroll: true,
            onSuccess: () => {
                editItemTarget.value = null;
            },
        },
    );
};

const loadVariants = async (productId) => {
    if (!productId) {
        variants.value = [];
        itemForm.product_variant_id = '';
        return;
    }

    loadingVariants.value = true;
    try {
        const res = await fetch(route('commerce.pricing.product-variants', productId));
        variants.value = await res.json();
    } finally {
        loadingVariants.value = false;
    }
};

watch(
    () => itemForm.product_id,
    (id) => {
        itemForm.product_variant_id = '';
        loadVariants(id);
    },
);
</script>

<template>
    <Head :title="priceList.name" />

    <AdminLayout :title="priceList.name">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4">
            <Link
                :href="route('commerce.pricing.price-lists.index')"
                class="text-sm font-medium text-brand-navy hover:text-brand-orange"
            >
                ← Price lists
            </Link>
        </div>

        <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
            <section class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">List settings</h2>
                <div>
                    <InputLabel value="Name" />
                    <TextInput v-model="metaForm.name" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel value="Code" />
                    <TextInput v-model="metaForm.code" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel value="Currency" />
                    <TextInput v-model="metaForm.currency" class="mt-1 block w-full" maxlength="3" />
                </div>
                <label class="flex items-center gap-2">
                    <Checkbox v-model:checked="metaForm.is_default" />
                    <span class="text-sm">Default list</span>
                </label>
                <label class="flex items-center gap-2">
                    <Checkbox v-model:checked="metaForm.is_active" />
                    <span class="text-sm">Active</span>
                </label>
                <PrimaryButton type="button" :disabled="metaForm.processing" @click="saveMeta">
                    Save settings
                </PrimaryButton>
            </section>

            <div class="space-y-6">
                <section class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Add price / tier</h2>
                    <p class="mt-1 text-xs text-gray-500">
                        Set min quantity for tier pricing (e.g. 10+ pcs = ৳95).
                    </p>
                    <form class="mt-4 grid gap-4 sm:grid-cols-2" @submit.prevent="addItem">
                        <div class="sm:col-span-2">
                            <InputLabel value="Product" />
                            <select
                                v-model="itemForm.product_id"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                required
                            >
                                <option value="">Select product…</option>
                                <option v-for="p in productOptions" :key="p.id" :value="p.id">
                                    {{ p.name }} {{ p.sku ? `(${p.sku})` : '' }}
                                    <template v-if="p.type === 'variant'"> — variant</template>
                                </option>
                            </select>
                            <InputError class="mt-1" :message="itemForm.errors.product_id" />
                        </div>
                        <div v-if="variants.length" class="sm:col-span-2">
                            <InputLabel value="Variant SKU" />
                            <select
                                v-model="itemForm.product_variant_id"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                            >
                                <option value="">All variants / select SKU</option>
                                <option v-for="v in variants" :key="v.id" :value="v.id">
                                    {{ v.sku }} {{ v.name ? `— ${v.name}` : '' }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Price" />
                            <TextInput v-model="itemForm.price" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                            <InputError class="mt-1" :message="itemForm.errors.price" />
                        </div>
                        <div>
                            <InputLabel value="Min quantity" />
                            <TextInput v-model="itemForm.min_quantity" type="number" min="1" class="mt-1 block w-full" required />
                            <InputError class="mt-1" :message="itemForm.errors.min_quantity" />
                        </div>
                        <div class="sm:col-span-2">
                            <PrimaryButton type="submit" :disabled="itemForm.processing">Add price</PrimaryButton>
                        </div>
                    </form>
                </section>

                <section class="admin-card">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold text-brand-navy">
                            Prices ({{ priceList.items.length }})
                        </h2>
                        <Link
                            :href="route('commerce.pricing.history.index', { price_list_id: priceList.id })"
                            class="text-xs font-medium text-brand-orange hover:underline"
                        >
                            View price history →
                        </Link>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500">
                                    <th class="pb-2">Product / SKU</th>
                                    <th class="pb-2">Min qty</th>
                                    <th class="pb-2">Price</th>
                                    <th class="pb-2 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in priceList.items" :key="item.id" class="border-t border-gray-100">
                                    <td class="py-2 font-medium text-brand-navy">{{ item.label }}</td>
                                    <td class="py-2 text-gray-600">{{ item.min_quantity }}</td>
                                    <td class="py-2 text-gray-800">
                                        {{ priceList.currency }} {{ Number(item.price).toFixed(2) }}
                                    </td>
                                    <td class="py-2 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                class="text-xs text-brand-navy hover:text-brand-orange"
                                                @click="openEditItem(item)"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                class="text-xs text-red-600 hover:text-red-700"
                                                @click="deleteItemTarget = item"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="priceList.items.length === 0">
                                    <td colspan="4" class="py-8 text-center text-gray-500">No prices in this list yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <DeleteConfirmModal
            :show="Boolean(deleteItemTarget)"
            title="Remove this price?"
            :item-name="deleteItemTarget?.label"
            confirm-label="Remove"
            :processing="deleteItemForm.processing"
            @close="deleteItemTarget = null"
            @confirm="confirmDeleteItem"
        />

        <Modal :show="Boolean(editItemTarget)" max-width="md" @close="closeEditItem">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-brand-navy">Edit price tier</h2>
                <p v-if="editItemTarget" class="mt-1 text-sm text-gray-500">{{ editItemTarget.label }}</p>

                <form class="mt-4 space-y-4" @submit.prevent="saveEditItem">
                    <div>
                        <InputLabel value="Price" />
                        <TextInput
                            v-model="editItemForm.price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-1" :message="editItemForm.errors.price" />
                    </div>
                    <div>
                        <InputLabel value="Min quantity" />
                        <TextInput
                            v-model="editItemForm.min_quantity"
                            type="number"
                            min="1"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-1" :message="editItemForm.errors.min_quantity" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <SecondaryButton type="button" @click="closeEditItem">Cancel</SecondaryButton>
                        <PrimaryButton type="submit" :disabled="editItemForm.processing">Save</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>
