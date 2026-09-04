<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    themes: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const activateForm = useForm({});

const activate = (id) => {
    activateForm.post(route('ecommerce.theme.installed.activate', id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Installed Themes" />

    <AdminLayout title="Installed Themes">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Name</th>
                            <th>Code</th>
                            <th>Active</th>
                            <th>Published</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="theme in themes" :key="theme.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ theme.name }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ theme.code }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="theme.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ theme.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="theme.is_published ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ theme.is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-right">
                                <PrimaryButton
                                    v-if="!theme.is_active"
                                    class="!py-1 !text-xs"
                                    :disabled="activateForm.processing"
                                    @click="activate(theme.id)"
                                >
                                    Activate
                                </PrimaryButton>
                                <span v-else class="text-xs text-gray-400">Current</span>
                            </td>
                        </tr>
                        <tr v-if="themes.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">
                                No themes installed.
                                <Link :href="route('ecommerce.theme.library')" class="text-brand-orange hover:underline">Browse library</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
