<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StoryPreviewPanel from '@/Components/Admin/StoryPreviewPanel.vue';
import DateTimePicker from '@/Components/Admin/DateTimePicker.vue';
import ToggleSwitch from '@/Components/Admin/ToggleSwitch.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';

const form = useForm({
    title: '',
    type: 'image',
    media: null,
    action_url: '',
    action_label: '',
    is_active: true,
    starts_at: '',
    expires_at: '',
});

const previewUrl = ref(null);
const isDragging = ref(false);
const fileInput = ref(null);
let blobUrl = null;

const previewType = computed(() => form.type);
const hasMedia = computed(() => Boolean(form.media));
const acceptTypes = computed(() =>
    form.type === 'video'
        ? 'video/mp4,video/webm,video/quicktime'
        : 'image/jpeg,image/png,image/webp',
);

const revokeBlob = () => {
    if (blobUrl) {
        URL.revokeObjectURL(blobUrl);
        blobUrl = null;
    }
};

const setMediaFile = (file) => {
    if (!file) {
        return;
    }

    revokeBlob();
    form.media = file;
    blobUrl = URL.createObjectURL(file);
    previewUrl.value = blobUrl;
};

watch(
    () => form.type,
    () => {
        form.media = null;
        revokeBlob();
        previewUrl.value = null;
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    },
);

onUnmounted(revokeBlob);

const onFileChange = (e) => {
    setMediaFile(e.target.files?.[0] ?? null);
};

const onDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer.files?.[0];
    if (file) setMediaFile(file);
};

const openFilePicker = () => {
    fileInput.value?.click();
};

const submit = () => {
    form.post(route('marketing.stories.store'), {
        forceFormData: true,
        onFinish: () => {
            form.reset('media');
            revokeBlob();
            previewUrl.value = null;
        },
    });
};
</script>

<template>
    <Head title="Create Story" />

    <AdminLayout title="Create Story">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500">
                Upload a vertical story with an optional tap-through link.
            </p>
            <Link
                :href="route('marketing.stories.index')"
                class="text-sm font-medium text-brand-navy hover:text-brand-orange"
            >
                ← Back to stories
            </Link>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,800px)_1fr]">
            <!-- LEFT: Form -->
            <form @submit.prevent="submit" class="min-w-0 space-y-5">
                <section class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Story format</h2>
                    <p class="mt-1 text-xs text-gray-500">9:16 vertical works best on mobile.</p>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="rounded-xl border-2 p-4 text-left transition-all"
                            :class="
                                form.type === 'image'
                                    ? 'border-brand-teal bg-brand-teal/10 ring-1 ring-brand-teal/30'
                                    : 'border-gray-200 hover:border-gray-300'
                            "
                            @click="form.type = 'image'"
                        >
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-brand-teal-dark shadow-sm">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <p class="mt-3 text-sm font-semibold text-brand-navy">Image</p>
                            <p class="text-xs text-gray-500">JPG, PNG, WebP · 10MB</p>
                        </button>

                        <button
                            type="button"
                            class="rounded-xl border-2 p-4 text-left transition-all"
                            :class="
                                form.type === 'video'
                                    ? 'border-brand-teal bg-brand-teal/10 ring-1 ring-brand-teal/30'
                                    : 'border-gray-200 hover:border-gray-300'
                            "
                            @click="form.type = 'video'"
                        >
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-brand-teal-dark shadow-sm">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <p class="mt-3 text-sm font-semibold text-brand-navy">Video</p>
                            <p class="text-xs text-gray-500">MP4, WebM · 50MB</p>
                        </button>
                    </div>
                </section>

                <section class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Media</h2>

                    <div
                        class="mt-4 rounded-xl border-2 border-dashed transition-colors"
                        :class="
                            isDragging
                                ? 'border-brand-teal bg-brand-teal/5'
                                : hasMedia
                                  ? 'border-brand-teal/40 bg-brand-teal/5'
                                  : 'border-gray-200 bg-gray-50'
                        "
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="onDrop"
                    >
                        <input
                            ref="fileInput"
                            id="media"
                            type="file"
                            class="hidden"
                            :accept="acceptTypes"
                            @change="onFileChange"
                        />

                        <div v-if="hasMedia" class="flex items-center justify-between gap-3 p-4">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-brand-navy">
                                    {{ form.media.name }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ (form.media.size / 1024 / 1024).toFixed(2) }} MB
                                </p>
                            </div>
                            <button
                                type="button"
                                class="shrink-0 text-sm font-medium text-brand-orange hover:text-brand-orange-dark"
                                @click="openFilePicker"
                            >
                                Replace
                            </button>
                        </div>

                        <button
                            v-else
                            type="button"
                            class="flex w-full flex-col items-center justify-center px-6 py-10 text-center"
                            @click="openFilePicker"
                        >
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-gray-400 shadow-sm ring-1 ring-gray-200">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </span>
                            <p class="mt-3 text-sm font-medium text-brand-navy">
                                Drop file here or click to upload
                            </p>
                            <p class="mt-1 text-xs text-gray-500">Vertical 9:16 recommended</p>
                        </button>
                    </div>
                    <InputError :message="form.errors.media" class="mt-2" />
                </section>

                <section class="admin-card space-y-4">
                    <h2 class="text-sm font-semibold text-brand-navy">Details</h2>

                    <div>
                        <InputLabel for="title" value="Title" />
                        <TextInput
                            id="title"
                            v-model="form.title"
                            class="mt-1.5 block w-full"
                            placeholder="Summer sale, New arrival…"
                        />
                        <InputError :message="form.errors.title" class="mt-1" />
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Schedule</p>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="starts_at" value="Start date" />
                                <DateTimePicker
                                    id="starts_at"
                                    v-model="form.starts_at"
                                    placeholder="Pick start date & time"
                                    class="mt-1.5"
                                />
                                <InputError :message="form.errors.starts_at" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="expires_at" value="End date" />
                                <DateTimePicker
                                    id="expires_at"
                                    v-model="form.expires_at"
                                    placeholder="Pick end date & time"
                                    class="mt-1.5"
                                />
                                <InputError :message="form.errors.expires_at" class="mt-1" />
                            </div>
                        </div>
                        <p class="mt-2 text-[11px] text-gray-400">
                            Leave empty to show immediately with no end date.
                        </p>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-white p-4">
                        <ToggleSwitch
                            v-model="form.is_active"
                            label="Visibility"
                            :description="form.is_active ? 'Story is visible on the store' : 'Story is hidden from customers'"
                        />
                        <InputError :message="form.errors.is_active" class="mt-2" />
                    </div>
                </section>

                <section class="admin-card space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold text-brand-navy">Action link</h2>
                        <p class="mt-1 text-xs text-gray-500">Optional button at the bottom of the story.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="action_url" value="URL" />
                            <TextInput
                                id="action_url"
                                v-model="form.action_url"
                                type="url"
                                class="mt-1.5 block w-full"
                                placeholder="https://…"
                            />
                            <InputError :message="form.errors.action_url" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="action_label" value="Button text" />
                            <TextInput
                                id="action_label"
                                v-model="form.action_label"
                                class="mt-1.5 block w-full"
                                placeholder="Shop now"
                            />
                            <InputError :message="form.errors.action_label" class="mt-1" />
                        </div>
                    </div>
                </section>

                <div class="flex flex-wrap items-center gap-3">
                    <PrimaryButton :disabled="form.processing || !hasMedia">
                        {{ form.processing ? 'Publishing…' : 'Publish story' }}
                    </PrimaryButton>
                    <Link :href="route('marketing.stories.index')">
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                </div>
            </form>

            <!-- RIGHT: Large preview zone -->
            <div
                class="hidden min-h-[560px] flex-col items-center justify-start rounded-2xl border border-gray-200 bg-gradient-to-br from-slate-50 via-white to-brand-teal/5 p-8 shadow-sm lg:flex"
            >
                <div class="text-center">
                    <p class="text-sm font-semibold text-brand-navy">Preview</p>
                    <p class="mt-1 text-xs text-gray-500">Story fullscreen view on the store</p>
                </div>

                <div class="mt-6 w-full max-w-[320px] px-2">
                    <StoryPreviewPanel
                        :preview-url="previewUrl"
                        :preview-type="previewType"
                        :title="form.title"
                        :action-label="form.action_label"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
