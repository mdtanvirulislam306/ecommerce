<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    headers: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
    importResult: { type: Object, default: null },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const exportStatus = ref('');
const fileInput = ref(null);

const importForm = useForm({
    file: null,
    update_existing: true,
});

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (exportStatus.value) {
        params.set('status', exportStatus.value);
    }
    const query = params.toString();

    return route('products.import-export.export') + (query ? `?${query}` : '');
});

const onFileChange = (event) => {
    importForm.file = event.target.files?.[0] ?? null;
};

const submitImport = () => {
    importForm.post(route('products.import-export.import'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            importForm.reset();
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};
</script>

<template>
    <Head title="Import / Export Products" />

    <AdminLayout title="Import / Export">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500">
                CSV import and export for simple products. Variant products are exported for reference but must be managed in the admin UI.
            </p>
            <Link
                :href="route('products.index')"
                class="text-sm font-medium text-brand-navy hover:text-brand-orange"
            >
                ← Back to products
            </Link>
        </div>

        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">Export products</h2>
                <p class="text-sm text-gray-500">
                    Download current catalog rows as CSV. Archived products are excluded unless you filter by archived status.
                </p>

                <div>
                    <InputLabel value="Filter by lifecycle status" />
                    <select v-model="exportStatus" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">All non-archived</option>
                        <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                </div>

                <a
                    :href="exportUrl"
                    class="inline-flex items-center rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white hover:bg-brand-navy/90"
                >
                    Download CSV
                </a>
            </section>

            <section class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">Import products</h2>
                <p class="text-sm text-gray-500">
                    Upload a CSV file. Rows are matched by SKU — existing simple products can be updated when the option below is enabled.
                </p>

                <form class="space-y-4" @submit.prevent="submitImport">
                    <div>
                        <InputLabel value="CSV file" />
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".csv,text/csv"
                            class="mt-1 block w-full text-sm text-gray-600"
                            @change="onFileChange"
                        />
                        <InputError class="mt-1" :message="importForm.errors.file" />
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <Checkbox v-model:checked="importForm.update_existing" />
                        Update existing products when SKU matches
                    </label>

                    <div class="flex flex-wrap gap-2">
                        <PrimaryButton type="submit" :disabled="importForm.processing || !importForm.file">
                            Import CSV
                        </PrimaryButton>
                        <a
                            :href="route('products.import-export.template')"
                            class="inline-flex items-center rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-brand-navy hover:bg-gray-50"
                        >
                            Download template
                        </a>
                    </div>
                </form>
            </section>
        </div>

        <section v-if="importResult?.errors?.length" class="admin-card mt-6">
            <h2 class="text-sm font-semibold text-red-700">Import errors</h2>
            <ul class="mt-3 space-y-2 text-sm text-red-700">
                <li v-for="(error, index) in importResult.errors" :key="index">
                    Row {{ error.row }}: {{ error.message }}
                </li>
            </ul>
        </section>

        <section class="admin-card mt-6">
            <h2 class="text-sm font-semibold text-brand-navy">CSV columns</h2>
            <p class="mt-2 text-sm text-gray-500">
                Required: <code class="text-brand-navy">sku</code>, <code class="text-brand-navy">name</code>.
                Reference fields resolve by name/code/slug (brand, primary_category, unit, product_family).
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span
                    v-for="header in headers"
                    :key="header"
                    class="rounded-md bg-gray-100 px-2 py-1 text-xs font-mono text-brand-navy"
                >
                    {{ header }}
                </span>
            </div>
            <p class="mt-4 text-sm text-gray-500">
                <Link :href="route('products.overview')" class="text-brand-orange hover:underline">← Back to overview</Link>
            </p>
        </section>
    </AdminLayout>
</template>
