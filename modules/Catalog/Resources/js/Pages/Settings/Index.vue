<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    settings: { type: Object, required: true },
    units: { type: Array, default: () => [] },
    productStatuses: { type: Array, default: () => [] },
    publicationStatuses: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const form = useForm({
    default_product_status: props.settings.default_product_status,
    default_publication_status: props.settings.default_publication_status,
    require_brand_on_create: props.settings.require_brand_on_create,
    require_primary_category_on_create: props.settings.require_primary_category_on_create,
    require_unit_on_create: props.settings.require_unit_on_create,
    auto_submit_for_review_on_create: props.settings.auto_submit_for_review_on_create,
    sku_prefix: props.settings.sku_prefix ?? '',
    default_unit_id: props.settings.default_unit_id ?? '',
    max_media_per_product: props.settings.max_media_per_product ?? 10,
});

const save = () => {
    form.put(route('products.settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Product Settings" />

    <AdminLayout title="Product Settings">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <p class="mb-5 text-sm text-gray-500">
            Catalog-wide defaults and validation rules applied when creating products and importing CSV rows.
        </p>

        <form class="max-w-2xl space-y-6" @submit.prevent="save">
            <section class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">Defaults for new products</h2>

                <div>
                    <InputLabel value="Default lifecycle status" />
                    <select v-model="form.default_product_status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option v-for="status in productStatuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.default_product_status" />
                </div>

                <div>
                    <InputLabel value="Default publication status" />
                    <select v-model="form.default_publication_status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option v-for="status in publicationStatuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.default_publication_status" />
                </div>

                <div>
                    <InputLabel value="Default unit" />
                    <select v-model="form.default_unit_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">None</option>
                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                            {{ unit.name }} ({{ unit.code }})
                        </option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.default_unit_id" />
                </div>

                <div>
                    <InputLabel for="sku_prefix" value="SKU prefix (optional)" />
                    <TextInput id="sku_prefix" v-model="form.sku_prefix" class="mt-1 block w-full" placeholder="e.g. PRD-" />
                    <InputError class="mt-1" :message="form.errors.sku_prefix" />
                </div>

                <div>
                    <InputLabel for="max_media" value="Max images per product" />
                    <TextInput id="max_media" v-model="form.max_media_per_product" type="number" min="1" max="50" class="mt-1 block w-full" />
                    <InputError class="mt-1" :message="form.errors.max_media_per_product" />
                </div>
            </section>

            <section class="admin-card space-y-3">
                <h2 class="text-sm font-semibold text-brand-navy">Creation rules</h2>

                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <Checkbox v-model:checked="form.require_brand_on_create" class="mt-0.5" />
                    Require brand when creating a product
                </label>

                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <Checkbox v-model:checked="form.require_primary_category_on_create" class="mt-0.5" />
                    Require primary category when creating a product
                </label>

                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <Checkbox v-model:checked="form.require_unit_on_create" class="mt-0.5" />
                    Require unit when creating a product
                </label>

                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <Checkbox v-model:checked="form.auto_submit_for_review_on_create" class="mt-0.5" />
                    Automatically submit new products for review (sets status to pending review)
                </label>
            </section>

            <div class="flex flex-wrap gap-3">
                <PrimaryButton type="submit" :disabled="form.processing">Save settings</PrimaryButton>
                <Link :href="route('products.overview')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
