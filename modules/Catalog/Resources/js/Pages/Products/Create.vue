<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MediaPicker from '@/Components/Admin/MediaPicker.vue';
import SearchableMultiSelect from '@/Components/Admin/SearchableMultiSelect.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    options: { type: Object, required: true },
});

const brands = ref([...(props.options.brands ?? [])]);
const categories = ref([...(props.options.categories ?? [])]);
const collections = ref([...(props.options.collections ?? [])]);

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

const libraryPreviews = ref([]);
const showMediaPicker = ref(false);
const quickModal = ref(null); // 'category' | 'brand' | 'collection' | null
const quickForm = useForm({ name: '', is_active: true });
const quickError = ref('');

const isSimple = computed(() => form.type === 'simple');
const isVariant = computed(() => form.type === 'variant');

const buildVariantRow = () => ({
    sku: '',
    barcode: '',
    name: '',
    weight: '',
    is_active: true,
    attributes: props.options.variant_attributes.map((attr) => ({
        attribute_id: attr.id,
        attribute_option_id: '',
    })),
});

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
        if (type === 'variant' && form.variants.length === 0) {
            form.variants.push(buildVariantRow());
        }
        if (type === 'simple') {
            form.variants = [];
        }
    },
);

const addVariant = () => form.variants.push(buildVariantRow());
const removeVariant = (index) => form.variants.splice(index, 1);

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
            body: JSON.stringify({
                name: quickForm.name,
                is_active: true,
            }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            const msg = data.errors?.name?.[0] || data.message || 'Could not create';
            quickError.value = msg;
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

const submit = () => {
    form.post(route('products.store'), {
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
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500">
                    Set up identity first — categories and brands can be created inline with +.
                </p>
            </div>
            <Link :href="route('products.index')" class="text-sm font-medium text-brand-navy hover:text-brand-orange">
                ← Back to products
            </Link>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <section class="admin-card">
                <h2 class="text-sm font-semibold text-brand-navy">1. Product type</h2>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-lg">
                    <button
                        type="button"
                        class="rounded-xl border-2 p-4 text-left transition-all"
                        :class="isSimple ? 'border-brand-teal bg-brand-teal/10 ring-1 ring-brand-teal/30' : 'border-gray-200 hover:border-gray-300'"
                        @click="form.type = 'simple'"
                    >
                        <p class="text-sm font-semibold text-brand-navy">Simple</p>
                        <p class="text-xs text-gray-500">Single SKU</p>
                    </button>
                    <button
                        type="button"
                        class="rounded-xl border-2 p-4 text-left transition-all"
                        :class="isVariant ? 'border-brand-teal bg-brand-teal/10 ring-1 ring-brand-teal/30' : 'border-gray-200 hover:border-gray-300'"
                        @click="form.type = 'variant'"
                    >
                        <p class="text-sm font-semibold text-brand-navy">Variant</p>
                        <p class="text-xs text-gray-500">Multiple SKUs</p>
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.type" />
            </section>

            <section class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">2. Basic information</h2>
                <div>
                    <InputLabel for="name" value="Product name" />
                    <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>
                <div>
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                    />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="internal_code" value="Internal code" />
                        <TextInput id="internal_code" v-model="form.internal_code" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="slug" value="Slug (optional)" />
                        <TextInput id="slug" v-model="form.slug" class="mt-1 block w-full" />
                    </div>
                </div>
            </section>

            <section v-if="isSimple" class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">3. Identifiers & pricing</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="sku" value="SKU" />
                        <TextInput id="sku" v-model="form.sku" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.sku" />
                    </div>
                    <div>
                        <InputLabel for="barcode" value="Barcode" />
                        <TextInput id="barcode" v-model="form.barcode" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="selling_price" value="Selling price" />
                        <TextInput id="selling_price" v-model="form.selling_price" type="number" min="0" step="0.01" class="mt-1 block w-full" />
                        <p class="mt-1 text-[11px] text-gray-500">Writes to Retail price list</p>
                    </div>
                    <div>
                        <InputLabel for="opening_stock" value="Opening stock" />
                        <TextInput id="opening_stock" v-model="form.opening_stock" type="number" min="0" step="0.01" class="mt-1 block w-full" />
                        <p class="mt-1 text-[11px] text-gray-500">Receives into Main warehouse</p>
                    </div>
                </div>
            </section>

            <section v-if="isVariant" class="admin-card space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-brand-navy">3. Variants</h2>
                    <SecondaryButton type="button" @click="addVariant">Add variant</SecondaryButton>
                </div>
                <div
                    v-for="(variant, index) in form.variants"
                    :key="index"
                    class="space-y-3 rounded-xl border border-gray-200 p-4"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-brand-navy">Variant {{ index + 1 }}</p>
                        <button
                            v-if="form.variants.length > 1"
                            type="button"
                            class="text-xs text-red-600"
                            @click="removeVariant(index)"
                        >
                            Remove
                        </button>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <InputLabel value="SKU" />
                            <TextInput v-model="variant.sku" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel value="Barcode" />
                            <TextInput v-model="variant.barcode" class="mt-1 block w-full" />
                        </div>
                    </div>
                    <div v-if="options.variant_attributes.length" class="grid gap-3 sm:grid-cols-2">
                        <div v-for="attrRow in variant.attributes" :key="attrRow.attribute_id">
                            <InputLabel :value="options.variant_attributes.find((a) => a.id === attrRow.attribute_id)?.name" />
                            <select
                                v-model="attrRow.attribute_option_id"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                            >
                                <option value="">Select…</option>
                                <option
                                    v-for="opt in options.variant_attributes.find((a) => a.id === attrRow.attribute_id)?.options"
                                    :key="opt.id"
                                    :value="opt.id"
                                >
                                    {{ opt.value }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-card space-y-5">
                <h2 class="text-sm font-semibold text-brand-navy">4. Classification</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Brand" />
                        <div class="mt-1">
                            <SearchableSelect
                                v-model="form.brand_id"
                                :options="brands"
                                placeholder="Search brand…"
                                creatable
                                create-label="Add brand"
                                @create="openQuick('brand')"
                            />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Primary category" />
                        <div class="mt-1">
                            <SearchableSelect
                                v-model="form.primary_category_id"
                                :options="categories"
                                placeholder="Search category…"
                                creatable
                                create-label="Add category"
                                @create="openQuick('category')"
                            />
                        </div>
                        <p class="mt-1 text-[11px] text-gray-500">Create missing categories with + before saving.</p>
                    </div>
                    <div>
                        <InputLabel value="Unit" />
                        <div class="mt-1">
                            <SearchableSelect
                                v-model="form.unit_id"
                                :options="options.units.map((u) => ({ id: u.id, name: `${u.name} (${u.code})` }))"
                                placeholder="Search unit…"
                            />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Product family" />
                        <div class="mt-1">
                            <SearchableSelect
                                v-model="form.product_family_id"
                                :options="options.families"
                                placeholder="Search family…"
                            />
                        </div>
                    </div>
                </div>

                <div>
                    <InputLabel value="Additional categories" />
                    <div class="mt-1">
                        <SearchableMultiSelect
                            v-model="form.category_ids"
                            :options="categories"
                            placeholder="Multi-select categories…"
                            search-placeholder="Search categories…"
                            creatable
                            create-label="Add category"
                            @create="openQuick('category')"
                        />
                    </div>
                </div>

                <div>
                    <InputLabel value="Collections" />
                    <div class="mt-1">
                        <SearchableMultiSelect
                            v-model="form.collection_ids"
                            :options="collections"
                            placeholder="Multi-select collections…"
                            creatable
                            create-label="Add collection"
                            @create="openQuick('collection')"
                        />
                    </div>
                </div>
            </section>

            <section v-if="options.informational_attributes.length" class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">5. Specifications</h2>
                <div
                    v-for="(row, index) in form.informational_attributes"
                    :key="row.attribute_id"
                    class="grid gap-2 sm:grid-cols-[180px_1fr] sm:items-center"
                >
                    <InputLabel :value="options.informational_attributes.find((a) => a.id === row.attribute_id)?.name" />
                    <select
                        v-if="options.informational_attributes.find((a) => a.id === row.attribute_id)?.input_type === 'select'"
                        v-model="row.attribute_option_id"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                    >
                        <option value="">Select…</option>
                        <option
                            v-for="opt in options.informational_attributes.find((a) => a.id === row.attribute_id)?.options"
                            :key="opt.id"
                            :value="opt.id"
                        >
                            {{ opt.value }}
                        </option>
                    </select>
                    <TextInput v-else v-model="row.value" />
                    <InputError :message="form.errors[`informational_attributes.${index}.value`]" />
                </div>
            </section>

            <section class="admin-card space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-brand-navy">Media</h2>
                        <p class="text-xs text-gray-500">Select from media library (upload there if needed).</p>
                    </div>
                    <SecondaryButton type="button" @click="showMediaPicker = true">Select from media</SecondaryButton>
                </div>
                <div v-if="libraryPreviews.length" class="flex flex-wrap gap-3">
                    <div
                        v-for="item in libraryPreviews"
                        :key="item.id"
                        class="group relative h-24 w-24 overflow-hidden rounded-xl ring-1 ring-gray-200"
                    >
                        <img :src="item.url" :alt="item.name" class="h-full w-full object-cover" />
                        <button
                            type="button"
                            class="absolute inset-x-0 bottom-0 bg-black/60 py-1 text-[10px] text-white opacity-0 transition group-hover:opacity-100"
                            @click="removeLibraryPreview(item.id)"
                        >
                            Remove
                        </button>
                    </div>
                </div>
                <p v-else class="rounded-lg border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-gray-400">
                    No images selected yet
                </p>
                <InputError :message="form.errors.media_library_ids || form.errors.media" />
            </section>

            <section class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">SEO & status</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="meta_title" value="Meta title" />
                        <TextInput id="meta_title" v-model="form.meta_title" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="status" value="Lifecycle status" />
                        <select
                            id="status"
                            v-model="form.status"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                        >
                            <option v-for="opt in options.product_statuses" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="publication_status" value="Publication status" />
                        <select
                            id="publication_status"
                            v-model="form.publication_status"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                        >
                            <option v-for="opt in options.publication_statuses" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                </div>
                <div>
                    <InputLabel for="meta_description" value="Meta description" />
                    <textarea
                        id="meta_description"
                        v-model="form.meta_description"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                    />
                </div>
            </section>

            <div class="sticky bottom-0 z-10 -mx-1 flex items-center gap-3 rounded-xl border border-gray-100 bg-white/95 px-4 py-3 shadow-sm backdrop-blur">
                <PrimaryButton :disabled="form.processing">Create product</PrimaryButton>
                <Link :href="route('products.index')" class="text-sm text-gray-500 hover:text-brand-navy">Cancel</Link>
            </div>
        </form>

        <MediaPicker :show="showMediaPicker" @close="showMediaPicker = false" @select="onMediaSelected" />

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
