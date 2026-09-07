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
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    product: { type: Object, required: true },
    options: { type: Object, required: true },
});

const brands = ref([...(props.options.brands ?? [])]);
const categories = ref([...(props.options.categories ?? [])]);
const collections = ref([...(props.options.collections ?? [])]);

const mapInformationalAttributes = () =>
    props.options.informational_attributes.map((attr) => {
        const existing = props.product.informational_attributes.find((a) => a.attribute_id === attr.id);
        return {
            attribute_id: attr.id,
            attribute_option_id: existing?.attribute_option_id || '',
            value: existing?.value || '',
        };
    });

const mapVariant = (variant) => ({
    id: variant.id,
    sku: variant.sku,
    barcode: variant.barcode || '',
    name: variant.name || '',
    weight: variant.weight || '',
    is_active: variant.is_active,
    media_library_ids: [],
    media_previews: [],
    existing_media: [...(variant.media || [])].slice(0, 1),
    attributes: (variant.attributes || [])
        .filter((a) => a.attribute_id && a.attribute_option_id)
        .map((a) => ({
            attribute_id: a.attribute_id,
            attribute_option_id: a.attribute_option_id,
        })),
});

const form = useForm({
    type: props.product.type,
    name: props.product.name,
    slug: props.product.slug,
    description: props.product.description || '',
    internal_code: props.product.internal_code || '',
    sku: props.product.sku || '',
    barcode: props.product.barcode || '',
    brand_id: props.product.brand_id || '',
    primary_category_id: props.product.primary_category_id || '',
    unit_id: props.product.unit_id || '',
    product_family_id: props.product.product_family_id || '',
    status: props.product.status,
    publication_status: props.product.publication_status,
    meta_title: props.product.meta_title || '',
    meta_description: props.product.meta_description || '',
    category_ids: [...props.product.category_ids],
    collection_ids: [...props.product.collection_ids],
    informational_attributes: mapInformationalAttributes(),
    variants: props.product.variants.map(mapVariant),
    media: [],
    media_library_ids: [],
    remove_media_ids: [],
    primary_media_id: props.product.media.find((m) => m.is_primary)?.id || props.product.media[0]?.id || null,
    selling_price: props.product.selling_price || '',
    opening_stock: '',
});

const libraryPreviews = ref([]);
const showMediaPicker = ref(false);
const showVariantMediaPicker = ref(false);
const variantMediaIndex = ref(null);
const existingMedia = ref(props.product.media.filter((m) => !m.product_variant_id));
const quickModal = ref(null);
const quickForm = useForm({ name: '', is_active: true });
const quickError = ref('');
const skuPrefix = computed(() => props.options.catalog_defaults?.sku_prefix ?? '');
const requirements = computed(() => props.options.catalog_requirements ?? {});
const showSeo = ref(false);
const showIds = ref(false);

const auto = reactive({
    slug: false,
    internal_code: false,
    sku: false,
    barcode: false,
});

const isSimple = computed(() => form.type === 'simple');
const isVariant = computed(() => form.type === 'variant');
const errorCount = computed(() => Object.keys(form.errors).length);
const previewTitle = computed(() => form.name?.trim() || props.product.name);
const visibleExistingMedia = computed(() =>
    existingMedia.value.filter((m) => !form.remove_media_ids.includes(m.id)),
);
const mediaCount = computed(() => visibleExistingMedia.value.length + libraryPreviews.value.length);

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

const unlockAuto = (field) => {
    auto[field] = true;
    applyAutoFromName(form.name);
};

const lockAuto = (field) => {
    auto[field] = false;
};

watch(
    () => form.type,
    (type) => {
        if (type === 'simple') form.variants = [];
    },
);

const toggleRemoveMedia = (id) => {
    const index = form.remove_media_ids.indexOf(id);
    if (index === -1) form.remove_media_ids.push(id);
    else form.remove_media_ids.splice(index, 1);
};

const openQuick = (type) => {
    quickModal.value = type;
    quickForm.reset();
    quickForm.is_active = true;
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

    for (const media of variant.existing_media || []) {
        if (!form.remove_media_ids.includes(media.id)) {
            form.remove_media_ids.push(media.id);
        }
    }
    variant.existing_media = [];
    variant.media_library_ids = [selected.id];
    variant.media_previews = [selected];
};

const removeVariantMediaPreview = (index) => {
    const variant = form.variants[index];
    if (!variant) return;
    variant.media_library_ids = [];
    variant.media_previews = [];
};

const removeVariantMedia = (index) => {
    const variant = form.variants[index];
    if (!variant) return;
    if (variant.media_previews?.length) {
        removeVariantMediaPreview(index);
        return;
    }
    const existing = variant.existing_media?.[0];
    if (existing) {
        markVariantExistingMediaRemove(index, existing.id);
    }
};

const markVariantExistingMediaRemove = (index, mediaId) => {
    const variant = form.variants[index];
    if (!variant) return;
    toggleRemoveMedia(mediaId);
    variant.existing_media = (variant.existing_media || []).filter((m) => m.id !== mediaId);
};

const infoAttr = (id) => props.options.informational_attributes.find((a) => a.id === id);

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            _method: 'put',
            variants: (data.variants || []).map((variant) => ({
                id: variant.id,
                sku: variant.sku,
                barcode: variant.barcode,
                name: variant.name,
                weight: variant.weight,
                is_active: variant.is_active,
                attributes: variant.attributes,
                media_library_ids: variant.media_library_ids || [],
            })),
        }))
        .post(route('products.update', props.product.id), {
            forceFormData: true,
        });
};
</script>

<template>
    <Head :title="`Edit ${product.name}`" />

    <AdminLayout :title="`Edit: ${product.name}`">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <Link
                    :href="route('products.show', product.id)"
                    class="mb-2 inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-brand-navy"
                >
                    <span aria-hidden="true">←</span> {{ product.name }}
                </Link>
                <h1 class="text-xl font-semibold tracking-tight text-brand-navy">Edit product</h1>
                <p class="mt-1 max-w-lg text-sm text-gray-500">
                    Update details, media, and variants. Identifiers stay unique on save.
                </p>
            </div>
            <div class="flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200">
                <span class="h-1.5 w-1.5 rounded-full" :class="isSimple ? 'bg-brand-teal' : 'bg-brand-navy'" />
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
                <div class="space-y-5">
                    <section class="admin-card !p-4 sm:!p-5 space-y-4">
                        <h2 class="text-sm font-semibold text-brand-navy">Title & description</h2>
                        <div>
                            <InputLabel for="name" value="Product name" />
                            <TextInput id="name" v-model="form.name" class="mt-1.5 block w-full text-base" required />
                            <InputError class="mt-1.5" :message="form.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                            />
                        </div>
                    </section>

                    <section v-if="isSimple" class="admin-card !p-4 sm:!p-5 space-y-4">
                        <div>
                            <h2 class="text-sm font-semibold text-brand-navy">Pricing & stock</h2>
                            <p class="mt-0.5 text-[11px] text-gray-400">
                                Current stock: {{ product.current_stock ?? '—' }}
                            </p>
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
                                    />
                                </div>
                            </div>
                            <div>
                                <InputLabel for="opening_stock" value="Opening stock (if none yet)" />
                                <TextInput
                                    id="opening_stock"
                                    v-model="form.opening_stock"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="mt-1.5 block w-full"
                                />
                            </div>
                        </div>
                    </section>

                    <section v-if="isVariant" class="admin-card !p-4 sm:!p-5 space-y-4">
                        <div>
                            <h2 class="text-sm font-semibold text-brand-navy">Variants</h2>
                            <p class="mt-0.5 text-[11px] text-gray-400">
                                Choose attributes → pick values → generate. Existing matches keep SKU & image.
                            </p>
                        </div>
                        <VariantMatrixBuilder
                            v-model="form.variants"
                            :variant-attributes="options.variant_attributes"
                            :product-name="form.name"
                            :sku-prefix="skuPrefix"
                            seed-from-variants
                            @open-media="openVariantMediaPicker"
                            @remove-media="removeVariantMedia"
                        />
                        <InputError :message="form.errors.variants" />
                    </section>

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
                                <TextInput id="meta_title" v-model="form.meta_title" class="mt-1.5 block w-full" />
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

                <aside class="space-y-5 xl:sticky xl:top-4 xl:self-start">
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

                    <section class="admin-card !p-4 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <h2 class="text-sm font-semibold text-brand-navy">Media</h2>
                                <p class="text-[11px] text-gray-400">
                                    {{ mediaCount }} image{{ mediaCount === 1 ? '' : 's' }} · click = primary
                                </p>
                            </div>
                            <button type="button" class="text-xs font-medium text-brand-teal hover:underline" @click="showMediaPicker = true">
                                Add
                            </button>
                        </div>

                        <div
                            v-if="existingMedia.length || libraryPreviews.length"
                            class="grid grid-cols-3 gap-2"
                        >
                            <button
                                v-for="media in existingMedia"
                                :key="media.id"
                                type="button"
                                class="relative aspect-square overflow-hidden rounded-lg ring-2 transition"
                                :class="
                                    form.remove_media_ids.includes(media.id)
                                        ? 'opacity-40 ring-red-300'
                                        : form.primary_media_id === media.id
                                          ? 'ring-brand-teal'
                                          : 'ring-gray-200'
                                "
                                @click="
                                    form.remove_media_ids.includes(media.id)
                                        ? toggleRemoveMedia(media.id)
                                        : ((form.primary_media_id = media.id),
                                          (form.remove_media_ids = form.remove_media_ids.filter((id) => id !== media.id)))
                                "
                                @contextmenu.prevent="toggleRemoveMedia(media.id)"
                            >
                                <img :src="media.url" alt="" class="h-full w-full object-cover" />
                                <span
                                    v-if="form.primary_media_id === media.id && !form.remove_media_ids.includes(media.id)"
                                    class="absolute left-1 top-1 rounded bg-brand-navy/85 px-1 py-0.5 text-[8px] text-white"
                                >
                                    Main
                                </span>
                            </button>
                            <div
                                v-for="item in libraryPreviews"
                                :key="item.id"
                                class="group relative aspect-square overflow-hidden rounded-lg ring-1 ring-brand-teal/40"
                            >
                                <img :src="item.url" :alt="item.name" class="h-full w-full object-cover" />
                                <button
                                    type="button"
                                    class="absolute inset-x-0 bottom-0 bg-black/55 py-0.5 text-[9px] text-white opacity-0 group-hover:opacity-100"
                                    @click="removeLibraryPreview(item.id)"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                        <button
                            v-else
                            type="button"
                            class="flex w-full flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-3 py-8 text-center transition hover:border-brand-teal/50"
                            @click="showMediaPicker = true"
                        >
                            <span class="text-xs text-gray-500">Add product images</span>
                        </button>
                        <p class="text-[10px] text-gray-400">Right-click an image to mark remove</p>
                    </section>

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
                                    @create="openQuick('collection')"
                                />
                            </div>
                            <InputError class="mt-1" :message="form.errors.collection_ids" />
                        </div>
                    </section>

                    <section class="admin-card !p-0 overflow-hidden">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-2 px-4 py-3.5 text-left"
                            @click="showIds = !showIds"
                        >
                            <div>
                                <h2 class="text-sm font-semibold text-brand-navy">Identifiers</h2>
                                <p class="truncate font-mono text-[10px] text-gray-400">{{ form.slug }}</p>
                            </div>
                            <span class="text-xs text-gray-400">{{ showIds ? 'Hide' : 'Edit' }}</span>
                        </button>
                        <div v-show="showIds" class="space-y-3 border-t border-gray-100 px-4 py-4">
                            <div>
                                <div class="flex items-center justify-between">
                                    <InputLabel for="slug" value="Slug" />
                                    <button type="button" class="text-[10px] font-medium text-brand-teal hover:underline" @click="unlockAuto('slug')">
                                        Regenerate
                                    </button>
                                </div>
                                <TextInput id="slug" v-model="form.slug" class="mt-1 block w-full font-mono text-xs" @input="lockAuto('slug')" />
                                <InputError class="mt-1" :message="form.errors.slug" />
                            </div>
                            <div>
                                <div class="flex items-center justify-between">
                                    <InputLabel for="internal_code" value="Internal code" />
                                    <button type="button" class="text-[10px] font-medium text-brand-teal hover:underline" @click="unlockAuto('internal_code')">
                                        Regenerate
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
                                        <button type="button" class="text-[10px] font-medium text-brand-teal hover:underline" @click="unlockAuto('sku')">
                                            Regenerate
                                        </button>
                                    </div>
                                    <TextInput id="sku" v-model="form.sku" class="mt-1 block w-full font-mono text-xs" @input="lockAuto('sku')" />
                                    <InputError class="mt-1" :message="form.errors.sku" />
                                </div>
                                <div>
                                    <div class="flex items-center justify-between">
                                        <InputLabel for="barcode" value="Barcode" />
                                        <button type="button" class="text-[10px] font-medium text-brand-teal hover:underline" @click="unlockAuto('barcode')">
                                            Regenerate
                                        </button>
                                    </div>
                                    <TextInput id="barcode" v-model="form.barcode" class="mt-1 block w-full font-mono text-xs" @input="lockAuto('barcode')" />
                                    <InputError class="mt-1" :message="form.errors.barcode" />
                                </div>
                            </template>
                        </div>
                    </section>
                </aside>
            </div>

            <div class="fixed bottom-0 left-0 right-0 z-20 border-t border-gray-200 bg-white/95 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-white/80 lg:left-72">
                <div class="mx-auto flex max-w-[1400px] flex-wrap items-center justify-between gap-3">
                    <p class="truncate text-sm text-gray-500">
                        <span class="font-medium text-brand-navy">{{ previewTitle }}</span>
                        <span v-if="isSimple && form.sku" class="ml-2 font-mono text-xs text-gray-400">{{ form.sku }}</span>
                    </p>
                    <div class="flex items-center gap-3">
                        <Link :href="route('products.show', product.id)" class="text-sm text-gray-500 hover:text-brand-navy">Cancel</Link>
                        <PrimaryButton :disabled="form.processing || !form.name.trim()">
                            {{ form.processing ? 'Saving…' : 'Save changes' }}
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
