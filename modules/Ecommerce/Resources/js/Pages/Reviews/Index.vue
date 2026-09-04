<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    reviews: { type: Object, required: true },
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
const deleteTarget = ref(null);

const bulkForm = useForm({ review_ids: [] });
const deleteForm = useForm({});
const meta = computed(() => paginationMeta(props.reviews));

const isPendingQueue = computed(() => queue.value === 'pending');

const statusMeta = {
    pending: { label: 'Pending', class: 'bg-amber-50 text-amber-800' },
    approved: { label: 'Approved', class: 'bg-emerald-50 text-emerald-700' },
    rejected: { label: 'Rejected', class: 'bg-red-50 text-red-700' },
};

const stars = (rating) => '★'.repeat(rating) + '☆'.repeat(5 - rating);

const toggleAll = (event) => {
    selected.value = event.target.checked ? props.reviews.data.map((r) => r.id) : [];
};

const toggleOne = (id) => {
    const i = selected.value.indexOf(id);
    if (i === -1) selected.value.push(id);
    else selected.value.splice(i, 1);
};

const bulkAction = (routeName) => {
    bulkForm.review_ids = [...selected.value];
    bulkForm.post(route(routeName), {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = [];
        },
    });
};

const openDeleteModal = (review) => {
    deleteTarget.value = review;
};

const closeDeleteModal = () => {
    if (!deleteForm.processing) deleteTarget.value = null;
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;

    deleteForm.delete(route('ecommerce.reviews.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

const visitIndex = () => {
    router.get(
        route('ecommerce.reviews.index'),
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
    <Head title="Product Reviews" />

    <AdminLayout title="Product Reviews">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <p class="mb-4 text-sm text-gray-500">
            Moderate customer reviews before they appear on the storefront. Reviews live in the Ecommerce domain, not Catalog PIM.
        </p>

        <div class="mb-5 grid gap-3 sm:grid-cols-3">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Pending</p>
                <p class="text-2xl font-semibold text-amber-700">{{ stats.pending }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Approved</p>
                <p class="text-2xl font-semibold text-emerald-700">{{ stats.approved }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Rejected</p>
                <p class="text-2xl font-semibold text-red-700">{{ stats.rejected }}</p>
            </div>
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-medium"
                        :class="queue === 'pending' ? 'bg-brand-navy text-white' : 'bg-gray-100 text-gray-600'"
                        @click="queue = 'pending'"
                    >
                        Pending
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-medium"
                        :class="queue === 'approved' ? 'bg-brand-navy text-white' : 'bg-gray-100 text-gray-600'"
                        @click="queue = 'approved'"
                    >
                        Approved
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-medium"
                        :class="queue === 'rejected' ? 'bg-brand-navy text-white' : 'bg-gray-100 text-gray-600'"
                        @click="queue = 'rejected'"
                    >
                        Rejected
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search reviews…"
                        class="rounded-md border-gray-300 text-sm"
                    />
                    <select v-model="perPage" class="rounded-md border-gray-300 text-sm" @change="visitIndex">
                        <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} / page</option>
                    </select>
                </div>
            </div>

            <div v-if="selected.length" class="admin-data-table__bulk border-b border-gray-100 px-4 py-2">
                <span class="text-sm text-gray-600">{{ selected.length }} selected</span>
                <div class="mt-2 flex flex-wrap gap-2">
                    <PrimaryButton v-if="isPendingQueue" type="button" @click="bulkAction('ecommerce.reviews.bulk-approve')">
                        Approve
                    </PrimaryButton>
                    <SecondaryButton
                        v-if="isPendingQueue || queue === 'approved'"
                        type="button"
                        @click="bulkAction('ecommerce.reviews.bulk-reject')"
                    >
                        Reject
                    </SecondaryButton>
                </div>
            </div>

            <table class="admin-data-table__table">
                <thead>
                    <tr>
                        <th class="w-10">
                            <input
                                type="checkbox"
                                :checked="selected.length === reviews.data.length && reviews.data.length > 0"
                                @change="toggleAll"
                            />
                        </th>
                        <th>Product</th>
                        <th>Author</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!reviews.data.length">
                        <td colspan="8" class="py-8 text-center text-sm text-gray-500">No reviews in this queue.</td>
                    </tr>
                    <tr v-for="review in reviews.data" :key="review.id">
                        <td>
                            <input type="checkbox" :checked="selected.includes(review.id)" @change="toggleOne(review.id)" />
                        </td>
                        <td>
                            <div class="font-medium text-brand-navy">{{ review.product_name }}</div>
                            <div v-if="review.product_sku" class="text-xs text-gray-500">{{ review.product_sku }}</div>
                        </td>
                        <td>
                            <div>{{ review.author_name }}</div>
                            <div v-if="review.author_email" class="text-xs text-gray-500">{{ review.author_email }}</div>
                        </td>
                        <td class="text-amber-500">{{ stars(review.rating) }}</td>
                        <td class="max-w-xs">
                            <div v-if="review.title" class="font-medium text-brand-navy">{{ review.title }}</div>
                            <p class="truncate text-sm text-gray-600">{{ review.body }}</p>
                        </td>
                        <td>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="statusMeta[review.status]?.class"
                            >
                                {{ statusMeta[review.status]?.label }}
                            </span>
                        </td>
                        <td class="text-sm text-gray-500">{{ formatDateTime(review.created_at) }}</td>
                        <td class="text-right">
                            <div class="flex justify-end gap-2">
                                <Link
                                    v-if="review.status === 'pending'"
                                    :href="route('ecommerce.reviews.approve', review.id)"
                                    method="post"
                                    as="button"
                                    class="text-sm text-emerald-700 hover:underline"
                                >
                                    Approve
                                </Link>
                                <Link
                                    v-if="review.status === 'pending' || review.status === 'approved'"
                                    :href="route('ecommerce.reviews.reject', review.id)"
                                    method="post"
                                    as="button"
                                    class="text-sm text-red-600 hover:underline"
                                >
                                    Reject
                                </Link>
                                <Link
                                    v-if="review.status === 'rejected'"
                                    :href="route('ecommerce.reviews.approve', review.id)"
                                    method="post"
                                    as="button"
                                    class="text-sm text-emerald-700 hover:underline"
                                >
                                    Re-approve
                                </Link>
                                <button type="button" class="text-sm text-gray-500 hover:text-red-600" @click="openDeleteModal(review)">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <TablePagination :meta="meta" />
        </div>

        <DeleteConfirmModal
            :show="deleteTarget !== null"
            title="Delete review"
            :message="deleteTarget ? `Remove review by ${deleteTarget.author_name}?` : ''"
            :processing="deleteForm.processing"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
