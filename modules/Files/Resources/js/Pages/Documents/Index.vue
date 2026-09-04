<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({ documents: { type: Object, required: true }, filters: { type: Object, default: () => ({}) } });
const flash = computed(() => usePage().props.flash);
const search = ref(props.filters.search ?? '');
const form = useForm({ title: '', file: null });
const onFile = (e) => { form.file = e.target.files[0]; };
const submit = () => form.post(route('files.documents.store'), { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() });
watch(search, (value) => router.get(route('files.documents.index'), { search: value || undefined }, { preserveState: true, replace: true }));
</script>
<template>
    <Head title="Documents" />
    <AdminLayout title="Documents">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm">{{ flash.success }}</div>
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search…" />
            <div><InputLabel value="Title" /><TextInput v-model="form.title" class="mt-1" /></div>
            <input type="file" @change="onFile" />
            <PrimaryButton type="button" :disabled="form.processing || !form.file || !form.title" @click="submit">Upload</PrimaryButton>
        </div>
        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Title</th><th class="pb-2">MIME</th><th class="pb-2">Size</th><th /></tr></thead>
                <tbody>
                    <tr v-for="row in documents.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2"><a :href="row.url" target="_blank" class="text-brand-orange">{{ row.title }}</a></td>
                        <td class="py-2">{{ row.mime }}</td>
                        <td class="py-2">{{ row.size }}</td>
                        <td class="py-2 text-right"><button type="button" class="text-xs text-red-600" @click="router.delete(route('files.documents.destroy', row.id))">Delete</button></td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="documents" class="mt-4" />
        </section>
    </AdminLayout>
</template>