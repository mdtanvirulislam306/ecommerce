<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    variants: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '', per_page: 25 }) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const meta = computed(() => paginationMeta(props.variants));

let searchTimer = null;
const visitIndex = () => {
    router.get(
        route('products.variants.index'),
        { search: search.value || undefined, per_page: perPage.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
</script>

<template>
    <Head title="Variants" />

    <AdminLayout title="Variants">
        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">All variants</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} SKUs across variant products</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search SKU or product…"
                        class="admin-data-table__search"
                    />
                    <Link
                        :href="route('products.create')"
                        class="inline-flex items-center rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                    >
                        Add product
                    </Link>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>SKU</th>
                            <th>Product</th>
                            <th>Attributes</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="variant in variants.data" :key="variant.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ variant.sku }}</td>
                            <td class="admin-data-table__cell">
                                <Link
                                    :href="route('products.show', variant.product_id)"
                                    class="text-brand-navy hover:text-brand-orange"
                                >
                                    {{ variant.product?.name || '—' }}
                                </Link>
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    v-for="av in variant.attribute_values"
                                    :key="av.id"
                                    class="mr-1 inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600"
                                >
                                    {{ av.option?.value }}
                                </span>
                                <span v-if="!variant.attribute_values?.length" class="text-gray-400">—</span>
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="variant.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ variant.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <Link
                                        :href="route('products.show', variant.product_id)"
                                        class="admin-data-table__action"
                                        title="View product"
                                    >
                                        <ActionIcon name="view" />
                                    </Link>
                                    <Link
                                        :href="route('products.edit', variant.product_id)"
                                        class="admin-data-table__action"
                                        title="Edit product"
                                    >
                                        <ActionIcon name="edit" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="variants.data.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">
                                    {{ search ? 'No variants match your search.' : 'No variants yet. Create a variant product to generate SKUs.' }}
                                </p>
                                <Link
                                    v-if="!search"
                                    :href="route('products.create')"
                                    class="mt-3 inline-block text-sm font-medium text-brand-orange hover:underline"
                                >
                                    Add variant product
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="admin-filter-select text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="variants" :links="variants.links" />
            </div>
        </div>
    </AdminLayout>
</template>
