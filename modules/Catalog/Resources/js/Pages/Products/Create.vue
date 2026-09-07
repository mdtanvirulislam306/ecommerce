<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MediaPicker from '@/Components/Admin/MediaPicker.vue';
import SearchableMultiSelect from '@/Components/Admin/SearchableMultiSelect.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import VariantMatrixBuilder from '@/Components/Admin/VariantMatrixBuilder.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { internalCodeFromName, skuFromName, slugify } from '@/utils/productIdentifiers';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref, watch } from 'vue';

const props = defineProps({
    options: { type: Object, required: true },
});

const brands = ref([...(props.options.brands ?? [])]);
const categories = ref([...(props.options.categories ?? [])]);
const collections = ref([...(props.options.collections ?? [])]);
const skuPrefix = computed(() => props.options.catalog_defaults?.sku_prefix ?? '');
const requirements = computed(() => props.options.catalog_requirements ?? {});

const form = useForm({
    type: 'simple',
    name: '',
    slug: '',
    description: '',
    internal_code: '',
    sku: '',
    barcode: '',
    brand_id: '',
    primary_category_id: '',
    unit_id: props.options.catalog_defaults?.unit_id ?? '',
    product_family_id: '',
    status: props.options.catalog_defaults?.status ?? 'draft',
    publication_status: props.options.catalog_defaults?.publication_status ?? 'not_published',
    meta_title: '',
    meta_description: '',
    category_ids: [],
    collection_ids: [],
    informational_attributes: [],
    variants: [],
    media: [],
    media_library_ids: [],
    selling_price: '',
    opening_stock: '',
});

const auto = reactive({
    slug: true,
    internal_code: true,
    sku: true,
    barcode: true,
});

const libraryPreviews = ref([]);
const showMediaPicker = ref(false);
const showVariantMediaPicker = ref(false);
const variantMediaIndex = ref(null);
const quickModal = ref(null);
const quickForm = useForm({ name: '', is_active: true });
const quickError = ref('');
const showSeo = ref(false);
const showIds = ref(false);

const isSimple = computed(() => form.type === 'simple');
const isVariant = computed(() => form.type === 'variant');
const errorCount = computed(() => Object.keys(form.errors).length);
const mediaCount = computed(() => libraryPreviews.value.length);
const previewTitle = computed(() => form.name?.trim() || 'Untitled product');

const applyAutoFromName = (name) => {
    if (auto.slug) form.slug = slugify(name);
    if (auto.internal_code) form.internal_code = internalCodeFromName(name);
    if (isSimple.value) {
        if (auto.sku) {
            form.sku = skuFromName(name, skuPrefix.value);
            if (auto.barcode) form.barcode = form.sku;
        } else if (auto.barcode && form.sku) {
            form.barcode = form.sku;
        }
    }
};

watch(
    () => form.name,
    (name) => applyAutoFromName(name),
);

watch(
    () => form.errors,
    (errors) => {
        if (errors.slug || errors.sku || errors.barcode || errors.internal_code) {
            showIds.value = true;
        }
    },
    { deep: true },
);

const lockAuto = (field) => {
    auto[field] = false;
};

const unlockAuto = (field) => {
    auto[field] = true;
    applyAutoFromName(form.name);
};

const initInformationalAttributes = () => {
    form.informational_attributes = props.options.informational_attributes.map((attr) => ({
        attribute_id: attr.id,
        attribute_option_id: '',
        value: '',
    }));
};

onMounted(() => initInformationalAttributes());

watch(
    () => form.type,
    (type) => {
        if (type === 'simple') {
            form.variants = [];
            applyAutoFromName(form.name);
        }
    },
);

const openQuick = (type) => {
    quickModal.value = type;
    quickForm.reset();
    quickForm.is_active = true;
    quickForm.clearErrors();
    quickError.value = '';
};

const quickRoute = computed(() => {
    if (quickModal.value === 'category') return route('products.categories.quick');
    if (quickModal.value === 'brand') return route('products.brands.quick');
    if (quickModal.value === 'collection') return route('products.collections.quick');
    return null;
});

const quickTitle = computed(() => {
    if (quickModal.value === 'category') return 'Add category';
    if (quickModal.value === 'brand') return 'Add brand';
    if (quickModal.value === 'collection') return 'Add collection';
    return '';
});

const submitQuick = async () => {
    quickError.value = '';
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    try {
        const res = await fetch(quickRoute.value, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ name: quickForm.name, is_active: true }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            quickError.value = data.errors?.name?.[0] || data.message || 'Could not create';
            return;
        }
        const item = data.item;
        if (quickModal.value === 'category') {
            categories.value = [...categories.value, item];
            if (!form.primary_category_id) form.primary_category_id = item.id;
            if (!form.category_ids.includes(item.id)) form.category_ids = [...form.category_ids, item.id];
        }
        if (quickModal.value === 'brand') {
            brands.value = [...brands.value, item];
            form.brand_id = item.id;
        }
        if (quickModal.value === 'collection') {
            collections.value = [...collections.value, item];
            if (!form.collection_ids.includes(item.id)) form.collection_ids = [...form.collection_ids, item.id];
        }
        quickModal.value = null;
    } catch {
        quickError.value = 'Network error — try again';
    }
};

const onMediaSelected = (items) => {
    const list = Array.isArray(items) ? items : items ? [items] : [];
    const ids = list.map((i) => i.id);
    form.media_library_ids = [...new Set([...form.media_library_ids, ...ids])];
    const map = Object.fromEntries(list.map((i) => [i.id, i]));
    libraryPreviews.value = [
        ...libraryPreviews.value.filter((p) => form.media_library_ids.includes(p.id)),
        ...ids.filter((id) => !libraryPreviews.value.some((p) => p.id === id)).map((id) => map[id]).filter(Boolean),
    ];
};

const removeLibraryPreview = (id) => {
    form.media_library_ids = form.media_library_ids.filter((x) => x !== id);
    libraryPreviews.value = libraryPreviews.value.filter((p) => p.id !== id);
};

const openVariantMediaPicker = (index) => {
    variantMediaIndex.value = index;
    showVariantMediaPicker.value = true;
};

const onVariantMediaSelected = (item) => {
    const index = variantMediaIndex.value;
    if (index === null || index === undefined) return;
    const selected = Array.isArray(item) ? item[0] : item;
    const variant = form.variants[index];
    if (!variant || !selected) return;
    variant.media_library_ids = [selected.id];
    variant.media_previews = [selected];
};

const removeVariantMediaPreview = (index) => {
    const variant = form.variants[index];
    if (!variant) return;
    variant.media_library_ids = [];
    variant.media_previews = [];
};

const infoAttr = (id) => props.options.informational_attributes.find((a) => a.id === id);

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            variants: (data.variants || []).map((variant) => ({
                sku: variant.sku,
                barcode: variant.barcode,
                name: variant.name,
                weight: variant.weight,
                is_active: variant.is_active,
                attributes: variant.attributes,
                media_library_ids: variant.media_library_ids || [],
            })),
        }))
        .post(route('products.store'), {
            forceFormData: true,
            onFinish: () => {
                form.media = [];
            },
        });
};
</script>

<template>
    <Head title="Add Product" />

    <AdminLayout title="Add Product">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <Link
                    :href="route('products.index')"
                    class="mb-2 inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-brand-navy"
                >
                    <span aria-hidden="true">←</span> Products
                </Link>
                <h1 class="text-xl font-semibold tracking-tight text-brand-navy">Add product</h1>
                <p class="mt-1 max-w-lg text-sm text-gray-500">
                    Start with the name — identifiers stay unique automatically. Add media and pricing when ready.
                </p>
            </div>
            <div class="flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200">
                <span
                    class="h-1.5 w-1.5 rounded-full"
                    :class="isSimple ? 'bg-brand-teal' : 'bg-brand-navy'"
                />
                {{ isSimple ? 'Simple product' : 'Variant product' }}
            </div>
        </div>

        <div
            v-if="errorCount"
            class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            <span class="mt-0.5 font-semibold">{{ errorCount }}</span>
            <p>Fix the highlighted fields below, then try again.</p>
        </div>

        <form class="pb-28" @submit.prevent="submit">
            <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_300px]">
                <!-- Main column -->
                <div class="space-y-5">
                    <!-- Type -->
                    <section class="admin-card !p-4 sm:!p-5">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h2 class="text-sm font-semibold text-brand-navy">Product type</h2>
                            <p class="text-[11px] text-gray-400">Choose once — variants unlock option rows</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2 rounded-xl bg-gray-50 p-1.5 ring-1 ring-gray-200/80">
                            <button
                                type="button"
                                class="rounded-lg px-3 py-3 text-left transition"
                                :class="
                                    isSimple
                                        ? 'bg-white shadow-sm ring-1 ring-black/5'
                                        : 'hover:bg-white/70'
                                "
                                @click="form.type = 'simple'"
                            >
                                <p class="text-sm font-semibold text-brand-navy">Simple</p>
                                <p class="mt-0.5 text-[11px] leading-snug text-gray-500">One SKU, one price, one stock</p>
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-3 py-3 text-left transition"
                                :class="
                                    isVariant
                                        ? 'bg-white shadow-sm ring-1 ring-black/5'
                                        : 'hover:bg-white/70'
                                "
                                @click="form.type = 'variant'"
                            >
                                <p class="text-sm font-semibold text-brand-navy">Variant</p>
                                <p class="mt-0.5 text-[11px] leading-snug text-gray-500">Size / color options with own image</p>
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.type" />
                    </section>

                    <!-- Title -->
                    <section class="admin-card !p-4 sm:!p-5 space-y-4">
                        <h2 class="text-sm font-semibold text-brand-navy">Title & description</h2>
                        <div>
                            <InputLabel for="name" value="Product name" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                class="mt-1.5 block w-full text-base"
                                placeholder="e.g. Organic Honey 500g"
                                required
                                autofocus
                            />
                            <InputError class="mt-1.5" :message="form.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                placeholder="What customers should know…"
                                class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                            />
                        </div>
                    </section>

                    <!-- Pricing (simple) -->
                    <section v-if="isSimple" class="admin-card !p-4 sm:!p-5 space-y-4">
                        <div>
                            <h2 class="text-sm font-semibold text-brand-navy">Pricing & stock</h2>
                            <p class="mt-0.5 text-[11px] text-gray-400">Writes to Retail price list and Main warehouse</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <InputLabel for="selling_price" value="Selling price" />
                                <div class="relative mt-1.5">
                                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-xs text-gray-400">৳</span>
                                    <TextInput
                                        id="selling_price"
                                        v-model="form.selling_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="block w-full pl-7"
                                        placeholder="0.00"
                                    />
                                </div>
                            </div>
                            <div>
                                <InputLabel for="opening_stock" value="Opening stock" />
                                <TextInput
                                    id="opening_stock"
                                    v-model="form.opening_stock"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="mt-1.5 block w-full"
                                    placeholder="0"
                                />
                            </div>
                        </div>
                    </section>

                    <!-- Variants -->
                    <section v-if="isVariant" class="admin-card !p-4 sm:!p-5 space-y-4">
                        <div>
                            <h2 class="text-sm font-semibold text-brand-navy">Variants</h2>
                            <p class="mt-0.5 text-[11px] text-gray-400">
                                Choose attributes → pick values → generate the SKU matrix
                            </p>
                        </div>
                        <VariantMatrixBuilder
                            v-model="form.variants"
                            :variant-attributes="options.variant_attributes"
                            :product-name="form.name"
                            :sku-prefix="skuPrefix"
                            @open-media="openVariantMediaPicker"
                            @remove-media="removeVariantMediaPreview"
                        />
                        <InputError :message="form.errors.variants" />
                        <p v-if="isVariant && !form.variants.length" class="text-[11px] text-amber-600">
                            Generate at least one variant before saving.
                        </p>
                    </section>

                    <!-- Specs -->
                    <section v-if="options.informational_attributes?.length" class="admin-card !p-4 sm:!p-5 space-y-4">
                        <h2 class="text-sm font-semibold text-brand-navy">Specifications</h2>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div v-for="(row, index) in form.informational_attributes" :key="row.attribute_id">
                                <InputLabel :value="infoAttr(row.attribute_id)?.name" />
                                <select
                                    v-if="infoAttr(row.attribute_id)?.input_type === 'select'"
                                    v-model="row.attribute_option_id"
                                    class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                                >
                                    <option value="">Select…</option>
                                    <option
                                        v-for="opt in infoAttr(row.attribute_id)?.options"
                                        :key="opt.id"
                                        :value="opt.id"
                                    >
                                        {{ opt.value }}
                                    </option>
                                </select>
                                <TextInput v-else v-model="row.value" class="mt-1.5 block w-full" />
                                <InputError :message="form.errors[`informational_attributes.${index}.value`]" />
                            </div>
                        </div>
                    </section>

                    <!-- SEO disclosure -->
                    <section class="admin-card !p-0 overflow-hidden">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left sm:px-5"
                            @click="showSeo = !showSeo"
                        >
                            <div>
                                <h2 class="text-sm font-semibold text-brand-navy">Search engine listing</h2>
                                <p class="text-[11px] text-gray-400">Optional meta title & description</p>
                            </div>
                            <span class="text-xs text-gray-400">{{ showSeo ? 'Hide' : 'Show' }}</span>
                        </button>
                        <div v-show="showSeo" class="space-y-3 border-t border-gray-100 px-4 py-4 sm:px-5">
                            <div>
                                <InputLabel for="meta_title" value="Meta title" />
                                <TextInput id="meta_title" v-model="form.meta_title" class="mt-1.5 block w-full" :placeholder="previewTitle" />
                            </div>
                            <div>
                                <InputLabel for="meta_description" value="Meta description" />
                                <textarea
                                    id="meta_description"
                                    v-model="form.meta_description"
                                    rows="2"
                                    class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                                />
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Side column -->
                <aside class="space-y-5 xl:sticky xl:top-4 xl:self-start">
                    <!-- Status -->
                    <section class="admin-card !p-4 space-y-3">
                        <h2 class="text-sm font-semibold text-brand-navy">Status</h2>
                        <div>
                            <InputLabel for="status" value="Lifecycle" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                            >
                                <option v-for="opt in options.product_statuses" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="publication_status" value="Storefront" />
                            <select
                                id="publication_status"
                                v-model="form.publication_status"
                                class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                            >
                                <option v-for="opt in options.publication_statuses" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>
                    </section>

                    <!-- Media -->
                    <section class="admin-card !p-4 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <h2 class="text-sm font-semibold text-brand-navy">Media</h2>
                                <p class="text-[11px] text-gray-400">{{ mediaCount }} selected</p>
                            </div>
                            <button
                                type="button"
                                class="text-xs font-medium text-brand-teal hover:underline"
                                @click="showMediaPicker = true"
                            >
                                {{ mediaCount ? 'Add more' : 'Browse' }}
                            </button>
                        </div>

                        <button
                            v-if="!mediaCount"
                            type="button"
                            class="flex w-full flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-3 py-8 text-center transition hover:border-brand-teal/50 hover:bg-brand-teal/5"
                            @click="showMediaPicker = true"
                        >
                            <svg class="h-7 w-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" />
                            </svg>
                            <span class="text-xs text-gray-500">Add product images</span>
                        </button>

                        <div v-else class="grid grid-cols-3 gap-2">
                            <div
                                v-for="(item, index) in libraryPreviews"
                                :key="item.id"
                                class="group relative aspect-square overflow-hidden rounded-lg ring-1 ring-gray-200"
                            >
                                <img :src="item.url" :alt="item.name" class="h-full w-full object-cover" />
                                <span
                                    v-if="index === 0"
                                    class="absolute left-1 top-1 rounded bg-brand-navy/85 px-1 py-0.5 text-[8px] font-medium text-white"
                                >
                                    Main
                                </span>
                                <button
                                    type="button"
                                    class="absolute inset-x-0 bottom-0 bg-black/55 py-0.5 text-[9px] text-white opacity-0 group-hover:opacity-100"
                                    @click="removeLibraryPreview(item.id)"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                        <InputError :message="form.errors.media_library_ids || form.errors.media" />
                    </section>

                    <!-- Organization -->
                    <section class="admin-card !p-4 space-y-3">
                        <h2 class="text-sm font-semibold text-brand-navy">Organization</h2>
                        <div>
                            <InputLabel :value="requirements.brand ? 'Brand *' : 'Brand'" />
                            <div class="mt-1.5">
                                <SearchableSelect
                                    v-model="form.brand_id"
                                    :options="brands"
                                    placeholder="Search brand…"
                                    creatable
                                    create-label="Add brand"
                                    @create="openQuick('brand')"
                                />
                            </div>
                            <InputError class="mt-1" :message="form.errors.brand_id" />
                        </div>
                        <div>
                            <InputLabel :value="requirements.primary_category ? 'Primary category *' : 'Primary category'" />
                            <div class="mt-1.5">
                                <SearchableSelect
                                    v-model="form.primary_category_id"
                                    :options="categories"
                                    placeholder="Search category…"
                                    creatable
                                    create-label="Add category"
                                    @create="openQuick('category')"
                                />
                            </div>
                            <InputError class="mt-1" :message="form.errors.primary_category_id" />
                        </div>
                        <div>
                            <InputLabel :value="requirements.unit ? 'Unit *' : 'Unit'" />
                            <div class="mt-1.5">
                                <SearchableSelect
                                    v-model="form.unit_id"
                                    :options="options.units.map((u) => ({ id: u.id, name: `${u.name} (${u.code})` }))"
                                    placeholder="Search unit…"
                                />
                            </div>
                            <InputError class="mt-1" :message="form.errors.unit_id" />
                        </div>
                        <div>
                            <InputLabel value="Product family" />
                            <div class="mt-1.5">
                                <SearchableSelect
                                    v-model="form.product_family_id"
                                    :options="options.families"
                                    placeholder="Search family…"
                                />
                            </div>
                            <InputError class="mt-1" :message="form.errors.product_family_id" />
                        </div>
                        <div>
                            <InputLabel value="More categories" />
                            <div class="mt-1.5">
                                <SearchableMultiSelect
                                    v-model="form.category_ids"
                                    :options="categories"
                                    placeholder="Add categories…"
                                    creatable
                                    create-label="Add category"
                                    @create="openQuick('category')"
                                />
                            </div>
                            <InputError class="mt-1" :message="form.errors.category_ids" />
                        </div>
                        <div>
                            <InputLabel value="Collections" />
                            <div class="mt-1.5">
                                <SearchableMultiSelect
                                    v-model="form.collection_ids"
                                    :options="collections"
                                    placeholder="Add collections…"
                                    creatable
                                    create-label="Add collection"
                                    @create="openQuick('collection')"
                                />
                            </div>
                            <InputError class="mt-1" :message="form.errors.collection_ids" />
                        </div>
                    </section>

                    <!-- Identifiers -->
                    <section class="admin-card !p-0 overflow-hidden">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-2 px-4 py-3.5 text-left"
                            @click="showIds = !showIds"
                        >
                            <div>
                                <h2 class="text-sm font-semibold text-brand-navy">Identifiers</h2>
                                <p class="truncate font-mono text-[10px] text-gray-400">
                                    {{ form.slug || 'slug-auto' }}
                                </p>
                            </div>
                            <span class="text-xs text-gray-400">{{ showIds ? 'Hide' : 'Edit' }}</span>
                        </button>
                        <div v-show="showIds" class="space-y-3 border-t border-gray-100 px-4 py-4">
                            <p class="text-[11px] leading-relaxed text-gray-400">
                                Backend keeps these unique. Same name → -01, -02…
                            </p>
                            <div>
                                <div class="flex items-center justify-between">
                                    <InputLabel for="slug" value="Slug" />
                                    <button
                                        v-if="!auto.slug"
                                        type="button"
                                        class="text-[10px] font-medium text-brand-teal hover:underline"
                                        @click="unlockAuto('slug')"
                                    >
                                        Auto
                                    </button>
                                </div>
                                <TextInput
                                    id="slug"
                                    v-model="form.slug"
                                    class="mt-1 block w-full font-mono text-xs"
                                    @input="lockAuto('slug')"
                                />
                                <InputError class="mt-1" :message="form.errors.slug" />
                            </div>
                            <div>
                                <div class="flex items-center justify-between">
                                    <InputLabel for="internal_code" value="Internal code" />
                                    <button
                                        v-if="!auto.internal_code"
                                        type="button"
                                        class="text-[10px] font-medium text-brand-teal hover:underline"
                                        @click="unlockAuto('internal_code')"
                                    >
                                        Auto
                                    </button>
                                </div>
                                <TextInput
                                    id="internal_code"
                                    v-model="form.internal_code"
                                    class="mt-1 block w-full font-mono text-xs"
                                    @input="lockAuto('internal_code')"
                                />
                                <InputError class="mt-1" :message="form.errors.internal_code" />
                            </div>
                            <template v-if="isSimple">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <InputLabel for="sku" value="SKU" />
                                        <button
                                            v-if="!auto.sku"
                                            type="button"
                                            class="text-[10px] font-medium text-brand-teal hover:underline"
                                            @click="unlockAuto('sku')"
                                        >
                                            Auto
                                        </button>
                                    </div>
                                    <TextInput
                                        id="sku"
                                        v-model="form.sku"
                                        class="mt-1 block w-full font-mono text-xs"
                                        @input="lockAuto('sku')"
                                    />
                                    <InputError class="mt-1" :message="form.errors.sku" />
                                </div>
                                <div>
                                    <div class="flex items-center justify-between">
                                        <InputLabel for="barcode" value="Barcode" />
                                        <button
                                            v-if="!auto.barcode"
                                            type="button"
                                            class="text-[10px] font-medium text-brand-teal hover:underline"
                                            @click="unlockAuto('barcode')"
                                        >
                                            Auto
                                        </button>
                                    </div>
                                    <TextInput
                                        id="barcode"
                                        v-model="form.barcode"
                                        class="mt-1 block w-full font-mono text-xs"
                                        @input="lockAuto('barcode')"
                                    />
                                    <InputError class="mt-1" :message="form.errors.barcode" />
                                </div>
                            </template>
                        </div>
                    </section>
                </aside>
            </div>

            <div
                class="fixed bottom-0 left-0 right-0 z-20 border-t border-gray-200 bg-white/95 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-white/80 lg:left-72"
            >
                <div class="mx-auto flex max-w-[1400px] flex-wrap items-center justify-between gap-3">
                    <p class="truncate text-sm text-gray-500">
                        <span class="font-medium text-brand-navy">{{ previewTitle }}</span>
                        <span v-if="isSimple && form.sku" class="ml-2 font-mono text-xs text-gray-400">{{ form.sku }}</span>
                    </p>
                    <div class="flex items-center gap-3">
                        <Link :href="route('products.index')" class="text-sm text-gray-500 hover:text-brand-navy">Cancel</Link>
                        <PrimaryButton :disabled="form.processing || !form.name.trim()">
                            {{ form.processing ? 'Creating…' : 'Create product' }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </form>

        <MediaPicker
            :show="showMediaPicker"
            :multiple="true"
            :selected-ids="form.media_library_ids"
            title="Select product images"
            @close="showMediaPicker = false"
            @select="onMediaSelected"
        />

        <MediaPicker
            :show="showVariantMediaPicker"
            :multiple="false"
            :selected-ids="variantMediaIndex != null ? form.variants[variantMediaIndex]?.media_library_ids || [] : []"
            title="Select variant image"
            @close="showVariantMediaPicker = false"
            @select="onVariantMediaSelected"
        />

        <Modal :show="!!quickModal" max-width="md" @close="quickModal = null">
            <div class="space-y-4 p-6">
                <h2 class="text-lg font-semibold text-brand-navy">{{ quickTitle }}</h2>
                <div>
                    <InputLabel value="Name" />
                    <TextInput v-model="quickForm.name" class="mt-1 block w-full" autofocus @keydown.enter.prevent="submitQuick" />
                    <p v-if="quickError" class="mt-2 text-sm text-red-600">{{ quickError }}</p>
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="quickModal = null">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="!quickForm.name?.trim()" @click="submitQuick">
                        Create & select
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
