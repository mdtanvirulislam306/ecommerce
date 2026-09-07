<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    attributes: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '', type: '', per_page: 25 }) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
    attributeTypes: { type: Array, default: () => [] },
    inputTypes: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const search = ref(props.filters.search ?? '');
const typeFilter = ref(props.filters.type ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const showFormModal = ref(false);
const editingAttribute = ref(null);
const deleteTarget = ref(null);

const emptyOption = () => ({ value: '', code: '' });

const emptyForm = () => ({
    name: '',
    code: '',
    type: 'variant',
    input_type: 'select',
    is_active: true,
    sort_order: 0,
    options: [emptyOption()],
});

const form = useForm(emptyForm());
const deleteForm = useForm({});

const meta = computed(() => paginationMeta(props.attributes));

const typeBadge = (type) =>
    type === 'variant'
        ? 'bg-brand-navy/10 text-brand-navy'
        : 'bg-brand-teal/10 text-brand-teal-dark';

const openCreate = () => {
    editingAttribute.value = null;
    form.defaults(emptyForm());
    form.reset();
    showFormModal.value = true;
};

const openEdit = (attribute) => {
    editingAttribute.value = attribute;
    form.defaults({
        name: attribute.name,
        code: attribute.code,
        type: attribute.type,
        input_type: attribute.input_type,
        is_active: attribute.is_active,
        sort_order: attribute.sort_order,
        options: attribute.options?.length
            ? attribute.options.map((o) => ({ value: o.value, code: o.code || '' }))
            : [emptyOption()],
    });
    form.reset();
    showFormModal.value = true;
};

const closeFormModal = () => {
    if (!form.processing) {
        showFormModal.value = false;
        editingAttribute.value = null;
    }
};

const addOption = () => {
    form.options.push(emptyOption());
};

const removeOption = (index) => {
    if (form.options.length > 1) {
        form.options.splice(index, 1);
    }
};

const submit = () => {
    const payload = { ...form.data() };
    if (payload.input_type !== 'select') {
        payload.options = [];
    }

    if (editingAttribute.value) {
        form.transform(() => payload).put(route('products.attributes.update', editingAttribute.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
                editingAttribute.value = null;
            },
        });
    } else {
        form.transform(() => payload).post(route('products.attributes.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
            },
        });
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleteForm.delete(route('products.attributes.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

let searchTimer = null;
const visitIndex = () => {
    router.get(
        route('products.attributes.index'),
        {
            search: search.value || undefined,
            type: typeFilter.value || undefined,
            per_page: perPage.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});

watch(typeFilter, visitIndex);
</script>

<template>
    <Head title="Attributes" />

    <AdminLayout title="Attributes">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Attributes</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search attributes…"
                        class="admin-data-table__search"
                    />
                    <select
                        v-model="typeFilter"
                        class="admin-filter-select text-xs"
                    >
                        <option value="">All types</option>
                        <option v-for="t in attributeTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                        @click="openCreate"
                    >
                        Add attribute
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Name</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Input</th>
                            <th>Options</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="attribute in attributes.data" :key="attribute.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ attribute.name }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ attribute.code }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                    :class="typeBadge(attribute.type)"
                                >
                                    {{ attribute.type }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-gray-600 capitalize">{{ attribute.input_type }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ attribute.options_count }}</td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <button
                                        type="button"
                                        class="admin-data-table__action"
                                        title="Edit"
                                        @click="openEdit(attribute)"
                                    >
                                        <ActionIcon name="edit" />
                                    </button>
                                    <button
                                        type="button"
                                        class="admin-data-table__action admin-data-table__action--danger"
                                        title="Delete"
                                        @click="deleteTarget = attribute"
                                    >
                                        <ActionIcon name="delete" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="attributes.data.length === 0">
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">
                                    {{
                                        search || typeFilter
                                            ? 'No attributes match your filters.'
                                            : 'No attributes yet. Add Color, Size, etc. before building variants.'
                                    }}
                                </p>
                                <button
                                    v-if="!search && !typeFilter"
                                    type="button"
                                    class="mt-3 text-sm font-medium text-brand-orange hover:underline"
                                    @click="openCreate"
                                >
                                    Add your first attribute
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="admin-filter-select text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="attributes" :links="attributes.links" />
            </div>
        </div>

        <Modal :show="showFormModal" max-width="3xl" @close="closeFormModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-brand-navy">
                    {{ editingAttribute ? 'Edit attribute' : 'Add attribute' }}
                </h3>
                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
                            <InputError class="mt-1" :message="form.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="code" value="Code" />
                            <TextInput id="code" v-model="form.code" class="mt-1 block w-full" required />
                            <InputError class="mt-1" :message="form.errors.code" />
                        </div>
                        <div>
                            <InputLabel for="type" value="Type" />
                            <select
                                id="type"
                                v-model="form.type"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                            >
                                <option v-for="t in attributeTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="input_type" value="Input type" />
                            <select
                                id="input_type"
                                v-model="form.input_type"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                            >
                                <option v-for="t in inputTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="form.input_type === 'select'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <InputLabel value="Options" />
                            <SecondaryButton type="button" @click="addOption">Add option</SecondaryButton>
                        </div>
                        <div
                            v-for="(option, index) in form.options"
                            :key="index"
                            class="flex items-end gap-3"
                        >
                            <div class="flex-1">
                                <TextInput v-model="option.value" placeholder="Value" class="block w-full" />
                                <InputError class="mt-1" :message="form.errors[`options.${index}.value`]" />
                            </div>
                            <div class="w-32">
                                <TextInput v-model="option.code" placeholder="Code" class="block w-full" />
                            </div>
                            <button
                                type="button"
                                class="text-xs text-red-600"
                                :disabled="form.options.length <= 1"
                                @click="removeOption(index)"
                            >
                                Remove
                            </button>
                        </div>
                    </div>

                    <label class="flex items-center gap-2">
                        <Checkbox v-model:checked="form.is_active" />
                        <span class="text-sm text-gray-700">Active</span>
                    </label>

                    <div class="flex justify-end gap-3 pt-2">
                        <SecondaryButton type="button" @click="closeFormModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <DeleteConfirmModal
            :show="Boolean(deleteTarget)"
            title="Delete attribute?"
            message="Variant and product data using this attribute may be affected."
            :item-name="deleteTarget?.name"
            confirm-label="Delete attribute"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
