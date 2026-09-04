<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StoryPreviewPanel from '@/Components/Admin/StoryPreviewPanel.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    story: {
        type: Object,
        required: true,
    },
});

const formatDate = formatDateTime;

const visibilityMeta = {
    visible: { label: 'Visible', class: 'bg-brand-teal/10 text-brand-teal-dark' },
    hidden: { label: 'Hidden', class: 'bg-gray-100 text-gray-600' },
    scheduled: { label: 'Scheduled', class: 'bg-blue-50 text-blue-700' },
    expired: { label: 'Expired', class: 'bg-orange-50 text-brand-orange' },
};
</script>

<template>
    <Head :title="story.title || 'Story'" />

    <AdminLayout :title="story.title || 'Story'">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Story details and preview.</p>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('marketing.stories.edit', story.id)"
                    class="text-sm font-medium text-brand-teal-dark hover:text-brand-teal"
                >
                    Edit
                </Link>
                <Link
                    :href="route('marketing.stories.index')"
                    class="text-sm font-medium text-brand-navy hover:text-brand-orange"
                >
                    ← Back to stories
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="admin-card space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-brand-navy">
                            {{ story.title || 'Untitled' }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">By {{ story.author }}</p>
                    </div>
                    <span
                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                        :class="
                            story.type === 'video'
                                ? 'bg-brand-navy/10 text-brand-navy'
                                : 'bg-brand-teal/10 text-brand-teal-dark'
                        "
                    >
                        {{ story.type }}
                    </span>
                </div>

                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Start</dt>
                        <dd class="mt-1 text-sm text-gray-700">{{ formatDate(story.starts_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Expires</dt>
                        <dd class="mt-1 text-sm text-gray-700">{{ formatDate(story.expires_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Visibility</dt>
                        <dd class="mt-1">
                            <span
                                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="visibilityMeta[story.visibility_status]?.class"
                            >
                                {{ visibilityMeta[story.visibility_status]?.label }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-700">{{ formatDate(story.created_at) }}</dd>
                    </div>
                </dl>

                <div v-if="story.action_url" class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Action link</p>
                    <p class="mt-1 text-sm font-medium text-brand-navy">{{ story.action_label || 'Open link' }}</p>
                    <a
                        :href="story.action_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-1 block truncate text-sm text-brand-orange hover:text-brand-orange-dark"
                    >
                        {{ story.action_url }}
                    </a>
                </div>
            </div>

            <div class="admin-card">
                <p class="text-sm font-semibold text-brand-navy">Preview</p>
                <p class="mt-0.5 text-xs text-gray-500">Fullscreen story view</p>
                <div class="mt-4 flex justify-center">
                    <div class="w-full max-w-[280px]">
                        <StoryPreviewPanel
                            :preview-url="story.media_url"
                            :preview-type="story.type"
                            :title="story.title"
                            :action-label="story.action_label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
