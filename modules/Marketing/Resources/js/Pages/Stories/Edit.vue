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

const props = defineProps({
    story: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    title: props.story.title ?? '',
    type: props.story.type,
    media: null,
    action_url: props.story.action_url ?? '',
    action_label: props.story.action_label ?? '',
    is_active: props.story.is_active,
    starts_at: props.story.starts_at ?? '',
    expires_at: props.story.expires_at ?? '',
});

const previewUrl = ref(props.story.media_url);
const isDragging = ref(false);
const fileInput = ref(null);
const blobUrl = ref(null);

const previewType = computed(() => form.type);
const hasNewMedia = computed(() => Boolean(form.media));
const acceptTypes = computed(() =>
    form.type === 'video'
        ? 'video/mp4,video/webm,video/quicktime'
        : 'image/jpeg,image/png,image/webp',
);

const revokeBlob = () => {
    if (blobUrl.value) {
        URL.revokeObjectURL(blobUrl.value);
        blobUrl.value = null;
    }
};

const setMediaFile = (file) => {
    if (!file) {
        return;
    }

    form.media = file;
};

watch(
    () => form.media,
    (file) => {
        revokeBlob();
        if (file) {
            blobUrl.value = URL.createObjectURL(file);
            previewUrl.value = blobUrl.value;
        } else {
            previewUrl.value = props.story.media_url;
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
    if (file) {
        setMediaFile(file);
    }
};

const openFilePicker = () => {
    fileInput.value?.click();
};

const submit = () => {
    form.put(route('marketing.stories.update', props.story.id), {
        forceFormData: true,
        onFinish: () => {
            form.reset('media');
            revokeBlob();
        },
    });
};
</script>

<template>
    <Head title="Edit Story" />

    <AdminLayout title="Edit Story">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Update story details, schedule, or media.</p>
            <Link
                :href="route('marketing.stories.index')"
                class="text-sm font-medium text-brand-navy hover:text-brand-orange"
            >
                ← Back to stories
            </Link>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,800px)_1fr]">
            <form @submit.prevent="submit" class="min-w-0 space-y-5">
                <section class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Story format</h2>
                    <p class="mt-1 text-xs text-gray-500">Change type only when replacing media.</p>

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
                            <p class="text-sm font-semibold text-brand-navy">Image</p>
                            <p class="text-xs text-gray-500">JPG, PNG, WebP</p>
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
                            <p class="text-sm font-semibold text-brand-navy">Video</p>
                            <p class="text-xs text-gray-500">MP4, WebM</p>
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
                                : hasNewMedia
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

                        <div v-if="hasNewMedia" class="flex items-center justify-between gap-3 p-4">
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

                        <div v-else class="flex items-center justify-between gap-3 p-4">
                            <p class="text-sm text-gray-600">Current {{ story.type }} file attached</p>
                            <button
                                type="button"
                                class="shrink-0 text-sm font-medium text-brand-orange hover:text-brand-orange-dark"
                                @click="openFilePicker"
                            >
                                Replace
                            </button>
                        </div>
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
                    <PrimaryButton :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save changes' }}
                    </PrimaryButton>
                    <Link :href="route('marketing.stories.index')">
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                </div>
            </form>

            <div
                class="hidden min-h-[560px] flex-col items-center justify-start rounded-2xl border border-gray-200 bg-gradient-to-br from-slate-50 via-white to-brand-teal/5 p-8 shadow-sm xl:flex"
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
