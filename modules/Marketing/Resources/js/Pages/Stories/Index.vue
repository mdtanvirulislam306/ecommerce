<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    stories: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', per_page: 10 }),
    },
    perPageOptions: {
        type: Array,
        default: () => [10, 25, 50, 100],
    },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 10);

const deleteForm = useForm({});
const deleteTarget = ref(null);

const openDeleteModal = (story) => {
    deleteTarget.value = story;
};

const closeDeleteModal = () => {
    if (!deleteForm.processing) {
        deleteTarget.value = null;
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) {
        return;
    }

    deleteForm.delete(route('marketing.stories.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

const typeBadge = (type) =>
    type === 'video'
        ? 'bg-brand-navy/10 text-brand-navy ring-1 ring-brand-navy/10'
        : 'bg-brand-teal/10 text-brand-teal-dark ring-1 ring-brand-teal/20';

const visibilityMeta = {
    visible: { label: 'Visible', class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    hidden: { label: 'Hidden', class: 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' },
    scheduled: { label: 'Scheduled', class: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200' },
    expired: { label: 'Expired', class: 'bg-orange-50 text-brand-orange ring-1 ring-orange-200' },
};

const meta = computed(() => paginationMeta(props.stories));
const hasStories = computed(() => meta.value.total > 0);
const showEmptyState = computed(() => !hasStories.value && !search.value);

let searchTimer = null;

const visitIndex = () => {
    router.get(
        route('marketing.stories.index'),
        {
            search: search.value || undefined,
            per_page: perPage.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search ?? '';
        perPage.value = filters.per_page ?? 10;
    },
);

const formatDate = formatDateTime;
</script>

<template>
    <Head title="Stories" />

    <AdminLayout title="Stories">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div v-if="showEmptyState" class="admin-card text-center">
            <p class="text-gray-500">No stories yet.</p>
            <Link
                :href="route('marketing.stories.create')"
                class="mt-3 inline-block text-sm font-medium text-brand-orange hover:text-brand-orange-dark"
            >
                Create your first story
            </Link>
        </div>

        <div v-else class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">All Stories</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative">
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search stories…"
                            class="admin-data-table__search"
                        />
                    </div>
                    <Link
                        :href="route('marketing.stories.create')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-orange-dark"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        New Story
                    </Link>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th class="w-16">Preview</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Start</th>
                            <th>Expires</th>
                            <th>Visibility</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="story in stories.data"
                            :key="story.id"
                            class="admin-data-table__row"
                        >
                            <td class="admin-data-table__cell">
                                <div class="h-11 w-11 overflow-hidden rounded-lg bg-gray-900 ring-1 ring-gray-200">
                                    <img
                                        v-if="story.type === 'image'"
                                        :src="story.media_url"
                                        alt=""
                                        class="h-full w-full object-cover"
                                    />
                                    <video
                                        v-else
                                        :src="story.media_url"
                                        class="h-full w-full object-cover"
                                        muted
                                        playsinline
                                    />
                                </div>
                            </td>
                            <td class="admin-data-table__cell">
                                <p class="font-medium text-brand-navy">
                                    {{ story.title || 'Untitled' }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ story.author }}</p>
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                    :class="typeBadge(story.type)"
                                >
                                    {{ story.type }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">
                                {{ formatDate(story.starts_at) }}
                            </td>
                            <td class="admin-data-table__cell text-gray-600">
                                {{ formatDate(story.expires_at) }}
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="visibilityMeta[story.visibility_status]?.class"
                                >
                                    {{ visibilityMeta[story.visibility_status]?.label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <Link
                                        :href="route('marketing.stories.show', story.id)"
                                        class="admin-data-table__action"
                                        title="View"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <Link
                                        :href="route('marketing.stories.edit', story.id)"
                                        class="admin-data-table__action"
                                        title="Edit"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button
                                        type="button"
                                        class="admin-data-table__action admin-data-table__action--danger"
                                        title="Delete"
                                        @click="openDeleteModal(story)"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="stories.data.length === 0">
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500">
                                No stories match your search.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="flex items-center gap-2 text-xs text-gray-600">
                        <span>Rows per page</span>
                        <select
                            v-model.number="perPage"
                            class="rounded-lg border border-gray-200 bg-white py-1.5 pl-2 pr-7 text-xs font-medium text-brand-navy shadow-sm focus:border-brand-teal focus:outline-none focus:ring-1 focus:ring-brand-teal"
                            @change="visitIndex"
                        >
                            <option v-for="option in perPageOptions" :key="option" :value="option">
                                {{ option }}
                            </option>
                        </select>
                    </label>
                    <span>
                        Showing {{ meta.from ?? 0 }}–{{ meta.to ?? 0 }} of {{ meta.total }}
                    </span>
                </div>

                <TablePagination :paginator="stories" :links="stories.links" />
            </div>
        </div>

        <DeleteConfirmModal
            :show="Boolean(deleteTarget)"
            title="Delete this story?"
            message="This story will be permanently removed from your store. This action cannot be undone."
            :item-name="deleteTarget?.title || 'Untitled story'"
            confirm-label="Delete story"
            :processing="deleteForm.processing"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
