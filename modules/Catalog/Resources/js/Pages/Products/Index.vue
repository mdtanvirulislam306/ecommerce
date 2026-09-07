<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: null, type: null, per_page: 25 }),
    },
    statusOptions: {
        type: Array,
        default: () => [],
    },
    typeOptions: {
        type: Array,
        default: () => [],
    },
    perPageOptions: {
        type: Array,
        default: () => [10, 25, 50, 100],
    },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const type = ref(props.filters.type ?? '');
const perPage = ref(props.filters.per_page ?? 25);

const deleteForm = useForm({});
const deleteTarget = ref(null);

const openDeleteModal = (product) => {
    deleteTarget.value = product;
};

const closeDeleteModal = () => {
    if (!deleteForm.processing) {
        deleteTarget.value = null;
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) {
        return;
    }

    deleteForm.delete(route('products.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

const statusMeta = {
    draft: { label: 'Draft', class: 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' },
    pending_review: { label: 'Pending Review', class: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' },
    approved: { label: 'Approved', class: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200' },
    active: { label: 'Active', class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    archived: { label: 'Archived', class: 'bg-orange-50 text-brand-orange ring-1 ring-orange-200' },
};

const typeBadge = (value) =>
    value === 'variant'
        ? 'bg-brand-navy/10 text-brand-navy ring-1 ring-brand-navy/10'
        : 'bg-brand-teal/10 text-brand-teal-dark ring-1 ring-brand-teal/20';

const meta = computed(() => paginationMeta(props.products));
const hasProducts = computed(() => meta.value.total > 0);
const hasActiveFilters = computed(() => Boolean(search.value || status.value || type.value));
const showEmptyCatalog = computed(() => !hasProducts.value && !hasActiveFilters.value);
const listTitle = computed(() => {
    if (!status.value) return 'All Products';
    return statusMeta[status.value]?.label
        ? `${statusMeta[status.value].label} products`
        : 'All Products';
});

let searchTimer = null;

const visitIndex = () => {
    router.get(
        route('products.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            type: type.value || undefined,
            per_page: perPage.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search ?? '';
        status.value = filters.status ?? '';
        type.value = filters.type ?? '';
        perPage.value = filters.per_page ?? 25;
    },
);

const setStatus = (value) => {
    status.value = value;
    visitIndex();
};

const clearFilters = () => {
    clearTimeout(searchTimer);
    search.value = '';
    status.value = '';
    type.value = '';
    visitIndex();
};

const formatDate = formatDateTime;
</script>

<template>
    <Head title="Products" />

    <AdminLayout :title="listTitle">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div v-if="showEmptyCatalog" class="admin-card text-center">
            <p class="text-gray-500">No products yet.</p>
            <Link
                :href="route('products.create')"
                class="mt-3 inline-block text-sm font-medium text-brand-orange hover:text-brand-orange-dark"
            >
                Add your first product
            </Link>
        </div>

        <div v-else class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">{{ listTitle }}</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <div class="relative">
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search products…"
                            class="admin-data-table__search"
                        />
                    </div>

                    <select v-model="type" class="admin-filter-select" @change="visitIndex">
                        <option value="">All types</option>
                        <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>

                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        class="text-xs font-medium text-gray-500 hover:text-brand-navy"
                        @click="clearFilters"
                    >
                        Clear
                    </button>

                    <Link
                        :href="route('products.create')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-orange-dark"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Product
                    </Link>
                </div>
            </div>

            <!-- Status chips -->
            <div class="flex flex-wrap gap-1.5 border-b border-gray-100 px-4 py-2.5 sm:px-5">
                <button
                    type="button"
                    class="rounded-full px-2.5 py-1 text-[11px] font-medium transition ring-1"
                    :class="
                        !status
                            ? 'bg-brand-navy text-white ring-brand-navy'
                            : 'bg-white text-gray-600 ring-gray-200 hover:ring-gray-300'
                    "
                    @click="setStatus('')"
                >
                    All
                </button>
                <button
                    v-for="opt in statusOptions"
                    :key="opt.value"
                    type="button"
                    class="rounded-full px-2.5 py-1 text-[11px] font-medium transition ring-1"
                    :class="
                        status === opt.value
                            ? 'bg-brand-navy text-white ring-brand-navy'
                            : 'bg-white text-gray-600 ring-gray-200 hover:ring-gray-300'
                    "
                    @click="setStatus(opt.value)"
                >
                    {{ opt.label }}
                </button>
            </div>

            <div v-if="!hasProducts" class="px-4 py-12 text-center sm:px-5">
                <p class="text-sm text-gray-500">No products match these filters.</p>
                <button type="button" class="mt-2 text-sm font-medium text-brand-teal hover:underline" @click="clearFilters">
                    Clear filters
                </button>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th class="w-16">Image</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Brand</th>
                            <th>Created</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="product in products.data"
                            :key="product.id"
                            class="admin-data-table__row"
                        >
                            <td class="admin-data-table__cell">
                                <div class="h-11 w-11 overflow-hidden rounded-lg bg-gray-100 ring-1 ring-gray-200">
                                    <img
                                        v-if="product.thumbnail_url"
                                        :src="product.thumbnail_url"
                                        alt=""
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center text-xs text-gray-400"
                                    >
                                        —
                                    </div>
                                </div>
                            </td>
                            <td class="admin-data-table__cell">
                                <p class="font-medium text-brand-navy">{{ product.name }}</p>
                                <p v-if="product.primary_category" class="mt-0.5 text-xs text-gray-400">
                                    {{ product.primary_category }}
                                </p>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">
                                {{ product.sku || (product.variants_count ? `${product.variants_count} variants` : '—') }}
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                    :class="typeBadge(product.type)"
                                >
                                    {{ product.type_label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="statusMeta[product.status]?.class"
                                >
                                    {{ product.status_label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ product.brand || '—' }}</td>
                            <td class="admin-data-table__cell text-gray-500">{{ formatDate(product.created_at) }}</td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <Link
                                        :href="route('products.show', product.id)"
                                        class="admin-data-table__action"
                                        title="View"
                                    >
                                        <ActionIcon name="view" />
                                    </Link>
                                    <Link
                                        :href="route('products.edit', product.id)"
                                        class="admin-data-table__action"
                                        title="Edit"
                                    >
                                        <ActionIcon name="edit" />
                                    </Link>
                                    <button
                                        type="button"
                                        class="admin-data-table__action admin-data-table__action--danger"
                                        title="Delete"
                                        @click="openDeleteModal(product)"
                                    >
                                        <ActionIcon name="delete" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="hasProducts" class="admin-data-table__footer">
                <TablePagination :paginator="products" :links="products.links" />
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">Per page</span>
                    <select v-model="perPage" class="admin-filter-select !py-1.5 text-xs" @change="visitIndex">
                        <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
                    </select>
                </div>
            </div>
        </div>

        <DeleteConfirmModal
            :show="!!deleteTarget"
            title="Delete product"
            :message="deleteTarget ? `Remove “${deleteTarget.name}”? This cannot be undone.` : ''"
            :processing="deleteForm.processing"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
