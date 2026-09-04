<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
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
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search SKU or product…"
                    class="admin-data-table__search"
                />
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
                            <td class="admin-data-table__cell text-right">
                                <Link
                                    :href="route('products.edit', variant.product_id)"
                                    class="admin-data-table__action"
                                >
                                    Edit product
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="variants.data.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">
                                No variants found. Create a variant product to add SKUs.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="variants" :links="variants.links" />
            </div>
        </div>
    </AdminLayout>
</template>
