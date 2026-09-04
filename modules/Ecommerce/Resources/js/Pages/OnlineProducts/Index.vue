<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    products: { type: Object, required: true },
    stats: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    publicationStatuses: { type: Array, default: () => [] },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const search = ref(props.filters.search ?? '');
const publication = ref(props.filters.publication ?? '');
const lifecycle = ref(props.filters.lifecycle ?? '');
const featured = ref(props.filters.featured ?? '');
const homepage = ref(props.filters.homepage ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const selected = ref([]);
const sortDrafts = ref({});

const bulkForm = useForm({ product_ids: [] });
const presentationForm = useForm({
    product_ids: [],
    is_featured: false,
    show_on_homepage: false,
});
const meta = computed(() => paginationMeta(props.products));

const pubMeta = {
    published: { label: 'Published', class: 'bg-emerald-50 text-emerald-700' },
    not_published: { label: 'Not published', class: 'bg-gray-100 text-gray-600' },
    unpublished: { label: 'Unpublished', class: 'bg-orange-50 text-brand-orange' },
};

const initSortDrafts = () => {
    const drafts = {};
    props.products.data.forEach((p) => {
        drafts[p.id] = p.storefront_sort_order ?? 0;
    });
    sortDrafts.value = drafts;
};

initSortDrafts();

watch(
    () => props.products.data,
    () => initSortDrafts(),
    { deep: true },
);

const toggleAll = (event) => {
    selected.value = event.target.checked ? props.products.data.map((p) => p.id) : [];
};

const toggleOne = (id) => {
    const i = selected.value.indexOf(id);
    if (i === -1) selected.value.push(id);
    else selected.value.splice(i, 1);
};

const bulkPublish = () => {
    bulkForm.product_ids = [...selected.value];
    bulkForm.post(route('ecommerce.online-products.bulk-publish'), {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = [];
        },
    });
};

const bulkUnpublish = () => {
    bulkForm.product_ids = [...selected.value];
    bulkForm.post(route('ecommerce.online-products.bulk-unpublish'), {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = [];
        },
    });
};

const bulkPresentation = (payload) => {
    presentationForm.product_ids = [...selected.value];
    presentationForm.is_featured = payload.is_featured ?? false;
    presentationForm.show_on_homepage = payload.show_on_homepage ?? false;
    presentationForm.post(route('ecommerce.online-products.bulk-presentation'), {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = [];
        },
    });
};

const updatePresentation = (productId, payload) => {
    router.put(route('ecommerce.online-products.update-presentation', productId), payload, {
        preserveScroll: true,
        preserveState: true,
    });
};

const toggleFeatured = (product) => {
    updatePresentation(product.id, { is_featured: !product.is_featured });
};

const toggleHomepage = (product) => {
    updatePresentation(product.id, { show_on_homepage: !product.show_on_homepage });
};

const saveSortOrder = (productId) => {
    const value = Number(sortDrafts.value[productId] ?? 0);
    updatePresentation(productId, { storefront_sort_order: value });
};

const visitIndex = () => {
    router.get(
        route('ecommerce.online-products.index'),
        {
            search: search.value || undefined,
            publication: publication.value || undefined,
            lifecycle: lifecycle.value || undefined,
            featured: featured.value === '' ? undefined : featured.value,
            homepage: homepage.value === '' ? undefined : homepage.value,
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

watch([publication, lifecycle, featured, homepage], visitIndex);
</script>

<template>
    <Head title="Online Products" />

    <AdminLayout title="Online Products">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <p class="mb-4 text-sm text-gray-500">
            Storefront visibility, featured placement, homepage blocks, and sort order are managed here — separate from Catalog PIM lifecycle.
        </p>

        <div class="mb-5 grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Published</p>
                <p class="text-2xl font-semibold text-emerald-700">{{ stats.published }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Not published</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.not_published }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Unpublished</p>
                <p class="text-2xl font-semibold text-brand-orange">{{ stats.unpublished }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Active, not on store</p>
                <p class="text-2xl font-semibold text-sky-700">{{ stats.active_not_published }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Featured</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.featured }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">On homepage</p>
                <p class="text-2xl font-semibold text-brand-teal-dark">{{ stats.homepage }}</p>
            </div>
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <h2 class="text-sm font-semibold text-brand-navy">Storefront catalog</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <select v-model="publication" class="rounded-lg border border-gray-200 text-xs">
                        <option value="">All publication</option>
                        <option v-for="s in publicationStatuses" :key="s.value" :value="s.value">
                            {{ s.label }}
                        </option>
                    </select>
                    <select v-model="lifecycle" class="rounded-lg border border-gray-200 text-xs">
                        <option value="">All lifecycle</option>
                        <option value="active">Active</option>
                        <option value="approved">Approved</option>
                        <option value="draft">Draft</option>
                        <option value="pending_review">Pending review</option>
                    </select>
                    <select v-model="featured" class="rounded-lg border border-gray-200 text-xs">
                        <option value="">All featured</option>
                        <option value="1">Featured only</option>
                        <option value="0">Not featured</option>
                    </select>
                    <select v-model="homepage" class="rounded-lg border border-gray-200 text-xs">
                        <option value="">All homepage</option>
                        <option value="1">Homepage only</option>
                        <option value="0">Not on homepage</option>
                    </select>
                </div>
            </div>

            <div v-if="selected.length" class="border-b border-gray-100 bg-gray-50/80 px-5 py-3 flex flex-wrap gap-2">
                <span class="text-xs text-gray-500">{{ selected.length }} selected</span>
                <PrimaryButton type="button" class="!py-1 !text-xs" @click="bulkPublish">Publish</PrimaryButton>
                <SecondaryButton type="button" class="!py-1 !text-xs" @click="bulkUnpublish">Unpublish</SecondaryButton>
                <SecondaryButton type="button" class="!py-1 !text-xs" @click="bulkPresentation({ is_featured: true })">
                    Mark featured
                </SecondaryButton>
                <SecondaryButton type="button" class="!py-1 !text-xs" @click="bulkPresentation({ is_featured: false })">
                    Remove featured
                </SecondaryButton>
                <SecondaryButton type="button" class="!py-1 !text-xs" @click="bulkPresentation({ show_on_homepage: true })">
                    Show on homepage
                </SecondaryButton>
                <SecondaryButton type="button" class="!py-1 !text-xs" @click="bulkPresentation({ show_on_homepage: false })">
                    Hide from homepage
                </SecondaryButton>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th class="w-10">
                                <input
                                    type="checkbox"
                                    :checked="selected.length === products.data.length && products.data.length > 0"
                                    @change="toggleAll"
                                />
                            </th>
                            <th>Product</th>
                            <th>Lifecycle</th>
                            <th>Storefront</th>
                            <th>Featured</th>
                            <th>Homepage</th>
                            <th>Sort</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="product in products.data" :key="product.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <input type="checkbox" :checked="selected.includes(product.id)" @change="toggleOne(product.id)" />
                            </td>
                            <td class="admin-data-table__cell">
                                <Link
                                    :href="route('products.show', product.id)"
                                    class="font-medium text-brand-navy hover:text-brand-orange"
                                >
                                    {{ product.name }}
                                </Link>
                                <p class="text-xs text-gray-400">{{ product.sku || product.type }}</p>
                            </td>
                            <td class="admin-data-table__cell capitalize text-gray-600">{{ product.status }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="pubMeta[product.publication_status]?.class"
                                >
                                    {{ product.publication_status_label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <button
                                    type="button"
                                    class="rounded-md px-2 py-1 text-xs font-medium"
                                    :class="product.is_featured ? 'bg-brand-navy text-white' : 'bg-gray-100 text-gray-500'"
                                    @click="toggleFeatured(product)"
                                >
                                    {{ product.is_featured ? 'Yes' : 'No' }}
                                </button>
                            </td>
                            <td class="admin-data-table__cell">
                                <button
                                    type="button"
                                    class="rounded-md px-2 py-1 text-xs font-medium"
                                    :class="product.show_on_homepage ? 'bg-brand-teal text-white' : 'bg-gray-100 text-gray-500'"
                                    @click="toggleHomepage(product)"
                                >
                                    {{ product.show_on_homepage ? 'Yes' : 'No' }}
                                </button>
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center gap-1">
                                    <TextInput
                                        v-model="sortDrafts[product.id]"
                                        type="number"
                                        min="0"
                                        class="!w-16 !py-1 !text-xs"
                                    />
                                    <button
                                        type="button"
                                        class="text-xs text-brand-orange hover:underline"
                                        @click="saveSortOrder(product.id)"
                                    >
                                        Save
                                    </button>
                                </div>
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="flex justify-end gap-1">
                                    <form
                                        v-if="product.can_publish"
                                        @submit.prevent="
                                            bulkForm.product_ids = [product.id];
                                            bulkForm.post(route('ecommerce.online-products.bulk-publish'), {
                                                preserveScroll: true,
                                            });
                                        "
                                    >
                                        <button type="submit" class="admin-data-table__action">Publish</button>
                                    </form>
                                    <form
                                        v-if="product.can_unpublish"
                                        @submit.prevent="
                                            bulkForm.product_ids = [product.id];
                                            bulkForm.post(route('ecommerce.online-products.bulk-unpublish'), {
                                                preserveScroll: true,
                                            });
                                        "
                                    >
                                        <button type="submit" class="admin-data-table__action">Unpublish</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="products.data.length === 0">
                            <td colspan="8" class="px-5 py-12 text-center text-sm text-gray-500">No products found.</td>
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
