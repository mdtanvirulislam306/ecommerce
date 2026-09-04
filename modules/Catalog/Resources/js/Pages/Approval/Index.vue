<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    products: { type: Object, required: true },
    stats: { type: Object, required: true },
    filters: { type: Object, default: () => ({ queue: 'pending', search: '', per_page: 25 }) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const queue = ref(props.filters.queue ?? 'pending');
const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const selected = ref([]);

const bulkForm = useForm({ product_ids: [] });
const meta = computed(() => paginationMeta(props.products));

const isPendingQueue = computed(() => queue.value === 'pending');

const toggleAll = (event) => {
    if (event.target.checked) {
        selected.value = props.products.data.map((p) => p.id);
    } else {
        selected.value = [];
    }
};

const toggleOne = (id) => {
    const index = selected.value.indexOf(id);
    if (index === -1) {
        selected.value.push(id);
    } else {
        selected.value.splice(index, 1);
    }
};

const bulkAction = (routeName) => {
    bulkForm.product_ids = [...selected.value];
    bulkForm.post(route(routeName), {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = [];
        },
    });
};

const visitIndex = () => {
    router.get(
        route('products.approval.index'),
        {
            queue: queue.value,
            search: search.value || undefined,
            per_page: perPage.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});

watch(queue, visitIndex);
</script>

<template>
    <Head title="Product Approval" />

    <AdminLayout title="Product Approval">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-5 grid gap-3 sm:grid-cols-3">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Pending review</p>
                <p class="text-2xl font-semibold text-amber-700">{{ stats.pending }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Approved (awaiting activation)</p>
                <p class="text-2xl font-semibold text-sky-700">{{ stats.approved }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Draft</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.draft }}</p>
                <Link :href="route('products.draft')" class="mt-1 text-xs text-brand-orange hover:underline">
                    View drafts →
                </Link>
            </div>
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                        :class="
                            isPendingQueue
                                ? 'bg-brand-navy text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        "
                        @click="queue = 'pending'"
                    >
                        Pending review
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                        :class="
                            !isPendingQueue
                                ? 'bg-brand-navy text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        "
                        @click="queue = 'approved'"
                    >
                        Ready to activate
                    </button>
                </div>
                <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
            </div>

            <div v-if="selected.length" class="border-b border-gray-100 bg-gray-50/80 px-5 py-3 flex flex-wrap gap-2">
                <span class="text-xs text-gray-500">{{ selected.length }} selected</span>
                <template v-if="isPendingQueue">
                    <PrimaryButton type="button" class="!py-1 !text-xs" @click="bulkAction('products.approval.bulk-approve')">
                        Approve
                    </PrimaryButton>
                    <SecondaryButton type="button" class="!py-1 !text-xs" @click="bulkAction('products.approval.bulk-reject')">
                        Reject
                    </SecondaryButton>
                </template>
                <template v-else>
                    <PrimaryButton type="button" class="!py-1 !text-xs" @click="bulkAction('products.approval.bulk-activate')">
                        Activate
                    </PrimaryButton>
                </template>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th class="w-10">
                                <input type="checkbox" :checked="selected.length === products.data.length && products.data.length > 0" @change="toggleAll" />
                            </th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Brand</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="product in products.data" :key="product.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <input type="checkbox" :checked="selected.includes(product.id)" @change="toggleOne(product.id)" />
                            </td>
                            <td class="admin-data-table__cell">
                                <Link :href="route('products.show', product.id)" class="font-medium text-brand-navy hover:text-brand-orange">
                                    {{ product.name }}
                                </Link>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ product.sku || '—' }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ product.brand || '—' }}</td>
                            <td class="admin-data-table__cell">
                                <div class="flex justify-end gap-1">
                                    <Link :href="route('products.show', product.id)" class="admin-data-table__action">View</Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="products.data.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">
                                {{ isPendingQueue ? 'No products pending review.' : 'No approved products awaiting activation.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="products" :links="products.links" />
            </div>
        </div>
    </AdminLayout>
</template>
