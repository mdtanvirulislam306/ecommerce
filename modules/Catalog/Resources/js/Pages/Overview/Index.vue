<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: { type: Object, required: true },
    recentProducts: { type: Array, default: () => [] },
});

const statusMeta = {
    draft: { label: 'Draft', class: 'bg-gray-100 text-gray-600' },
    pending_review: { label: 'Pending review', class: 'bg-amber-50 text-amber-800' },
    approved: { label: 'Approved', class: 'bg-sky-50 text-sky-700' },
    active: { label: 'Active', class: 'bg-emerald-50 text-emerald-700' },
    archived: { label: 'Archived', class: 'bg-gray-100 text-gray-500' },
};

const pubMeta = {
    published: { label: 'Published', class: 'bg-emerald-50 text-emerald-700' },
    not_published: { label: 'Not published', class: 'bg-gray-100 text-gray-600' },
    unpublished: { label: 'Unpublished', class: 'bg-orange-50 text-brand-orange' },
};

const total = computed(() => Number(props.stats.products.total) || 0);

const pct = (n) => {
    if (!total.value) return 0;
    return Math.min(100, Math.round((Number(n) / total.value) * 100));
};

const kpiCards = computed(() => [
    {
        label: 'Total products',
        value: props.stats.products.total,
        hint: 'Entire catalog',
        href: 'products.index',
        accent: 'border-l-brand-navy',
        valueClass: 'text-brand-navy',
    },
    {
        label: 'Active',
        value: props.stats.products.active,
        hint: 'Ready to sell',
        href: 'products.active',
        accent: 'border-l-emerald-500',
        valueClass: 'text-emerald-700',
    },
    {
        label: 'Pending review',
        value: props.stats.products.pending_review,
        hint: 'Needs approval',
        href: 'products.approval.index',
        accent: 'border-l-amber-500',
        valueClass: 'text-amber-700',
    },
    {
        label: 'Draft',
        value: props.stats.products.draft,
        hint: 'Still editing',
        href: 'products.draft',
        accent: 'border-l-gray-400',
        valueClass: 'text-brand-navy',
    },
]);

const lifecycleRows = computed(() => [
    { label: 'Draft', value: props.stats.products.draft, bar: 'bg-gray-400' },
    { label: 'Pending review', value: props.stats.products.pending_review, bar: 'bg-amber-500' },
    { label: 'Approved', value: props.stats.products.approved, bar: 'bg-sky-500' },
    { label: 'Active', value: props.stats.products.active, bar: 'bg-emerald-500' },
    { label: 'Archived', value: props.stats.products.archived, bar: 'bg-gray-300' },
]);

const typeRows = computed(() => [
    { label: 'Simple products', value: props.stats.products.simple },
    { label: 'Variant products', value: props.stats.products.variant },
    { label: 'With media', value: props.stats.media.products_with_media },
]);

const publicationCards = computed(() => [
    {
        label: 'Published',
        value: props.stats.publication.published,
        class: 'border-emerald-100 bg-emerald-50/60',
        valueClass: 'text-emerald-700',
        bar: 'bg-emerald-500',
    },
    {
        label: 'Not published',
        value: props.stats.publication.not_published,
        class: 'border-gray-100 bg-gray-50',
        valueClass: 'text-brand-navy',
        bar: 'bg-gray-400',
    },
    {
        label: 'Unpublished',
        value: props.stats.publication.unpublished,
        class: 'border-orange-100 bg-orange-50/50',
        valueClass: 'text-brand-orange',
        bar: 'bg-brand-orange',
    },
]);

const masterData = computed(() => [
    { label: 'Brands', value: props.stats.master_data.brands, href: 'products.brands.index' },
    { label: 'Categories', value: props.stats.master_data.categories, href: 'products.categories.index' },
    { label: 'Collections', value: props.stats.master_data.collections, href: 'products.collections.index' },
    { label: 'Units', value: props.stats.master_data.units, href: 'products.units.index' },
    { label: 'SKU variants', value: props.stats.master_data.variants, href: 'products.variants.index' },
    { label: 'Attributes', value: props.stats.master_data.attributes, href: null },
    { label: 'Families', value: props.stats.master_data.families, href: null },
]);

const primaryActions = [
    { label: 'Add product', href: 'products.create', primary: true },
    { label: 'Pending approval', href: 'products.approval.index', primary: false },
    { label: 'Import / Export', href: 'products.import-export.index', primary: false },
];

const secondaryActions = [
    { label: 'All products', href: 'products.index', desc: 'Browse & filter catalog' },
    { label: 'Brands', href: 'products.brands.index', desc: 'Brand master data' },
    { label: 'Categories', href: 'products.categories.index', desc: 'Category tree' },
    { label: 'Settings', href: 'products.settings.index', desc: 'Catalog defaults' },
];
</script>

<template>
    <Head title="Products Overview" />

    <AdminLayout title="Products Overview">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-xl">
                <p class="text-sm leading-relaxed text-gray-500">
                    PIM snapshot — lifecycle, storefront publication, and master data in one place.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="action in primaryActions"
                    :key="action.href"
                    :href="route(action.href)"
                    class="inline-flex items-center rounded-lg px-3.5 py-2 text-sm font-medium transition"
                    :class="
                        action.primary
                            ? 'bg-brand-navy text-white hover:bg-brand-navy/90'
                            : 'border border-gray-200 bg-white text-brand-navy hover:border-brand-teal/40 hover:bg-gray-50'
                    "
                >
                    {{ action.label }}
                </Link>
            </div>
        </div>

        <!-- KPI strip -->
        <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <Link
                v-for="card in kpiCards"
                :key="card.label"
                :href="route(card.href)"
                class="admin-card border-l-4 p-5 transition hover:border-brand-teal/30 hover:shadow-md"
                :class="card.accent"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ card.label }}</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight" :class="card.valueClass">{{ card.value }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ card.hint }}</p>
            </Link>
        </div>

        <div class="mb-6 grid gap-6 lg:grid-cols-5">
            <!-- Lifecycle -->
            <section class="admin-card lg:col-span-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-brand-navy">Lifecycle</h2>
                        <p class="mt-0.5 text-xs text-gray-400">Share of catalog by status</p>
                    </div>
                    <span class="rounded-md bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-500">
                        {{ total }} total
                    </span>
                </div>

                <ul class="mt-5 space-y-3.5">
                    <li v-for="row in lifecycleRows" :key="row.label">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="text-gray-600">{{ row.label }}</span>
                            <span class="tabular-nums font-semibold text-brand-navy">
                                {{ row.value }}
                                <span class="ml-1 font-normal text-gray-400">({{ pct(row.value) }}%)</span>
                            </span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full transition-all"
                                :class="row.bar"
                                :style="{ width: `${pct(row.value)}%` }"
                            />
                        </div>
                    </li>
                </ul>

                <div class="mt-6 grid gap-3 border-t border-gray-100 pt-5 sm:grid-cols-3">
                    <div
                        v-for="row in typeRows"
                        :key="row.label"
                        class="rounded-lg bg-gray-50 px-3.5 py-3"
                    >
                        <p class="text-xs text-gray-500">{{ row.label }}</p>
                        <p class="mt-1 text-lg font-semibold text-brand-navy">{{ row.value }}</p>
                    </div>
                </div>
            </section>

            <!-- Publication -->
            <section class="admin-card lg:col-span-2">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Storefront</h2>
                    <p class="mt-0.5 text-xs text-gray-400">Publication status</p>
                </div>

                <div class="mt-5 space-y-3">
                    <div
                        v-for="card in publicationCards"
                        :key="card.label"
                        class="rounded-xl border px-4 py-3.5"
                        :class="card.class"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-medium text-gray-500">{{ card.label }}</p>
                            <p class="text-xl font-semibold tabular-nums" :class="card.valueClass">{{ card.value }}</p>
                        </div>
                        <div class="mt-2.5 h-1 overflow-hidden rounded-full bg-white/70">
                            <div
                                class="h-full rounded-full"
                                :class="card.bar"
                                :style="{ width: `${pct(card.value)}%` }"
                            />
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="mb-6 grid gap-6 lg:grid-cols-5">
            <!-- Master data -->
            <section class="admin-card lg:col-span-3">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-brand-navy">Master data</h2>
                        <p class="mt-0.5 text-xs text-gray-400">Click any tile to manage</p>
                    </div>
                </div>

                <div class="grid gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
                    <component
                        :is="item.href ? Link : 'div'"
                        v-for="item in masterData"
                        :key="item.label"
                        :href="item.href ? route(item.href) : undefined"
                        class="flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50/80 px-3.5 py-3"
                        :class="
                            item.href
                                ? 'group transition hover:border-brand-teal/30 hover:bg-white hover:shadow-sm'
                                : ''
                        "
                    >
                        <span
                            class="text-sm text-gray-600"
                            :class="item.href ? 'group-hover:text-brand-navy' : ''"
                        >
                            {{ item.label }}
                        </span>
                        <span class="text-base font-semibold tabular-nums text-brand-navy">{{ item.value }}</span>
                    </component>
                </div>
            </section>

            <!-- Shortcuts -->
            <section class="admin-card lg:col-span-2">
                <h2 class="text-sm font-semibold text-brand-navy">Shortcuts</h2>
                <p class="mt-0.5 text-xs text-gray-400">Common catalog tasks</p>

                <ul class="mt-4 space-y-2">
                    <li v-for="action in secondaryActions" :key="action.href">
                        <Link
                            :href="route(action.href)"
                            class="flex items-center justify-between gap-3 rounded-xl border border-transparent px-3 py-2.5 transition hover:border-gray-100 hover:bg-gray-50"
                        >
                            <div>
                                <p class="text-sm font-medium text-brand-navy">{{ action.label }}</p>
                                <p class="text-xs text-gray-400">{{ action.desc }}</p>
                            </div>
                            <span class="text-gray-300" aria-hidden="true">→</span>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>

        <!-- Recent products -->
        <section class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Recently updated</h2>
                    <p class="mt-0.5 text-xs text-gray-400">Latest changes in the catalog</p>
                </div>
                <Link
                    :href="route('products.index')"
                    class="text-xs font-medium text-brand-orange hover:underline"
                >
                    View all →
                </Link>
            </div>

            <div v-if="!recentProducts.length" class="px-5 py-12 text-center text-sm text-gray-500">
                No products yet.
                <Link :href="route('products.create')" class="ml-1 font-medium text-brand-orange hover:underline">
                    Create one
                </Link>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Product</th>
                            <th>Lifecycle</th>
                            <th>Storefront</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="product in recentProducts"
                            :key="product.id"
                            class="admin-data-table__row"
                        >
                            <td class="admin-data-table__cell">
                                <Link
                                    :href="route('products.show', product.id)"
                                    class="font-medium text-brand-navy hover:text-brand-orange"
                                >
                                    {{ product.name }}
                                </Link>
                                <p v-if="product.sku" class="text-xs text-gray-400">{{ product.sku }}</p>
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="statusMeta[product.status]?.class"
                                >
                                    {{ statusMeta[product.status]?.label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="pubMeta[product.publication_status]?.class"
                                >
                                    {{ pubMeta[product.publication_status]?.label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-gray-500">
                                {{ formatDateTime(product.updated_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AdminLayout>
</template>
