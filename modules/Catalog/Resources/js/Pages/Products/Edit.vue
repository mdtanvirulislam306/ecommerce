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
import { computed, ref, watch } from 'vue';

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
    attributes: props.options.variant_attributes.map((attr) => {
        const existing = variant.attributes.find((a) => a.attribute_id === attr.id);
        return {
            attribute_id: attr.id,
            attribute_option_id: existing?.attribute_option_id || '',
        };
    }),
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
const existingMedia = ref([...props.product.media]);
const quickModal = ref(null);
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

watch(
    () => form.type,
    (type) => {
        if (type === 'variant' && form.variants.length === 0) form.variants.push(buildVariantRow());
        if (type === 'simple') form.variants = [];
    },
);

const addVariant = () => form.variants.push(buildVariantRow());
const removeVariant = (index) => form.variants.splice(index, 1);

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

const submit = () => {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('products.update', props.product.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="`Edit ${product.name}`" />

    <AdminLayout :title="`Edit: ${product.name}`">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <p class="text-sm text-gray-500">Update product details. Use + to create related records inline.</p>
            <Link :href="route('products.show', product.id)" class="text-sm font-medium text-brand-navy hover:text-brand-orange">
                ← Back to product
            </Link>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <section class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">Basic information</h2>
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
                        <InputLabel for="slug" value="Slug" />
                        <TextInput id="slug" v-model="form.slug" class="mt-1 block w-full" />
                    </div>
                </div>
            </section>

            <section v-if="isSimple" class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">Identifiers & pricing</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="sku" value="SKU" />
                        <TextInput id="sku" v-model="form.sku" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <InputLabel for="barcode" value="Barcode" />
                        <TextInput id="barcode" v-model="form.barcode" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="selling_price" value="Selling price" />
                        <TextInput id="selling_price" v-model="form.selling_price" type="number" min="0" step="0.01" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="opening_stock" value="Opening stock (if none yet)" />
                        <TextInput id="opening_stock" v-model="form.opening_stock" type="number" min="0" step="0.01" class="mt-1 block w-full" />
                        <p class="mt-1 text-[11px] text-gray-500">Current: {{ product.current_stock ?? '—' }}</p>
                    </div>
                </div>
            </section>

            <section v-if="isVariant" class="admin-card space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-brand-navy">Variants</h2>
                    <SecondaryButton type="button" @click="addVariant">Add variant</SecondaryButton>
                </div>
                <div
                    v-for="(variant, index) in form.variants"
                    :key="variant.id ?? `new-${index}`"
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
                </div>
            </section>

            <section class="admin-card space-y-5">
                <h2 class="text-sm font-semibold text-brand-navy">Classification</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Brand" />
                        <div class="mt-1">
                            <SearchableSelect
                                v-model="form.brand_id"
                                :options="brands"
                                placeholder="Search brand…"
                                creatable
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
                                @create="openQuick('category')"
                            />
                        </div>
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
                            creatable
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
                            @create="openQuick('collection')"
                        />
                    </div>
                </div>
            </section>

            <section class="admin-card space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-brand-navy">Media</h2>
                        <p class="text-xs text-gray-500">Click existing image to mark removal. Add more from library.</p>
                    </div>
                    <SecondaryButton type="button" @click="showMediaPicker = true">Select from media</SecondaryButton>
                </div>
                <div v-if="existingMedia.length" class="flex flex-wrap gap-3">
                    <button
                        v-for="media in existingMedia"
                        :key="media.id"
                        type="button"
                        class="relative rounded-xl ring-2 transition"
                        :class="
                            form.remove_media_ids.includes(media.id)
                                ? 'opacity-40 ring-red-300'
                                : form.primary_media_id === media.id
                                  ? 'ring-brand-teal'
                                  : 'ring-gray-200'
                        "
                        @click="toggleRemoveMedia(media.id)"
                    >
                        <img :src="media.url" alt="" class="h-20 w-20 rounded-xl object-cover" />
                    </button>
                </div>
                <div v-if="libraryPreviews.length" class="flex flex-wrap gap-3">
                    <div
                        v-for="item in libraryPreviews"
                        :key="item.id"
                        class="group relative h-20 w-20 overflow-hidden rounded-xl ring-1 ring-gray-200"
                    >
                        <img :src="item.url" :alt="item.name" class="h-full w-full object-cover" />
                        <button
                            type="button"
                            class="absolute inset-x-0 bottom-0 bg-black/60 py-0.5 text-[10px] text-white opacity-0 group-hover:opacity-100"
                            @click="removeLibraryPreview(item.id)"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </section>

            <section class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">Status</h2>
                <div class="grid gap-4 sm:grid-cols-2">
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
            </section>

            <div class="sticky bottom-0 z-10 flex items-center gap-3 rounded-xl border border-gray-100 bg-white/95 px-4 py-3 shadow-sm backdrop-blur">
                <PrimaryButton :disabled="form.processing">Save changes</PrimaryButton>
                <Link :href="route('products.show', product.id)" class="text-sm text-gray-500 hover:text-brand-navy">Cancel</Link>
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
