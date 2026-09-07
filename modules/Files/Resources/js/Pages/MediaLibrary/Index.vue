<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    items: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const flash = computed(() => usePage().props.flash);
const search = ref(props.filters.search ?? '');
const fileInput = ref(null);
const form = useForm({ files: [] });
const dragOver = ref(false);

const onFiles = (fileList) => {
    form.files = [...(fileList || [])];
};

const submit = () => {
    if (!form.files.length) return;
    form.post(route('files.media-library.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('files');
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const onDrop = (e) => {
    dragOver.value = false;
    onFiles(e.dataTransfer?.files);
};

let searchTimer = null;
watch(search, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(
            route('files.media-library.index'),
            { search: value || undefined },
            { preserveState: true, replace: true },
        );
    }, 300);
});

const formatSize = (bytes) => {
    const n = Number(bytes) || 0;
    if (n < 1024) return `${n} B`;
    if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`;
    return `${(n / (1024 * 1024)).toFixed(1)} MB`;
};
</script>

<template>
    <Head title="Media Library" />

    <AdminLayout title="Media Library">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm text-gray-500">Shared gallery for products and other modules. Multi-select upload supported.</p>
            </div>
            <TextInput v-model="search" type="search" class="w-full sm:w-72" placeholder="Search media…" />
        </div>

        <div
            class="mb-6 rounded-2xl border-2 border-dashed px-4 py-8 text-center transition"
            :class="dragOver ? 'border-brand-teal bg-brand-teal/5' : 'border-gray-200 bg-white'"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="onDrop"
        >
            <p class="text-sm font-medium text-brand-navy">Drop images here or choose files</p>
            <p class="mt-1 text-xs text-gray-400">JPEG, PNG, WebP · up to 20 files</p>
            <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                <input
                    ref="fileInput"
                    type="file"
                    class="hidden"
                    accept="image/*"
                    multiple
                    @change="onFiles($event.target.files)"
                />
                <SecondaryButton type="button" @click="fileInput?.click()">Choose files</SecondaryButton>
                <PrimaryButton type="button" :disabled="form.processing || !form.files.length" @click="submit">
                    Upload {{ form.files.length ? `(${form.files.length})` : '' }}
                </PrimaryButton>
            </div>
            <p v-if="form.files.length" class="mt-3 text-xs text-gray-500">
                {{ form.files.map((f) => f.name).join(', ') }}
            </p>
            <p v-if="form.errors.files || form.errors.file" class="mt-2 text-sm text-red-600">
                {{ form.errors.files || form.errors.file }}
            </p>
        </div>

        <section class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <h2 class="text-sm font-semibold text-brand-navy">Gallery</h2>
                <p class="text-xs text-gray-400">{{ items.total ?? items.data?.length ?? 0 }} files</p>
            </div>

            <div v-if="!items.data?.length" class="px-5 py-12 text-center text-sm text-gray-500">
                No media yet. Upload your first images above.
            </div>

            <div v-else class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                <div
                    v-for="row in items.data"
                    :key="row.id"
                    class="group overflow-hidden rounded-xl border border-gray-100 bg-gray-50"
                >
                    <a :href="row.url" target="_blank" class="block aspect-square overflow-hidden bg-white">
                        <img
                            v-if="row.mime?.startsWith('image/')"
                            :src="row.url"
                            :alt="row.name"
                            class="h-full w-full object-cover transition group-hover:scale-105"
                            loading="lazy"
                        />
                        <div v-else class="flex h-full items-center justify-center p-3 text-center text-xs text-gray-500">
                            {{ row.name }}
                        </div>
                    </a>
                    <div class="space-y-1 px-2.5 py-2">
                        <p class="truncate text-xs font-medium text-brand-navy" :title="row.name">{{ row.name }}</p>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10px] text-gray-400">{{ formatSize(row.size) }}</span>
                            <button
                                type="button"
                                class="text-[10px] font-medium text-red-600 hover:underline"
                                @click="router.delete(route('files.media-library.destroy', row.id), { preserveScroll: true })"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-data-table__footer">
                <TablePagination :paginator="items" :links="items.links" />
            </div>
        </section>
    </AdminLayout>
</template>
