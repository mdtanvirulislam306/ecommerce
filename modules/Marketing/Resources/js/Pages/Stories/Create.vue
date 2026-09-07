<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StoryPreviewPanel from '@/Components/Admin/StoryPreviewPanel.vue';
import MediaPicker from '@/Components/Admin/MediaPicker.vue';
import DateTimePicker from '@/Components/Admin/DateTimePicker.vue';
import ToggleSwitch from '@/Components/Admin/ToggleSwitch.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const form = useForm({
    title: '',
    type: 'image',
    media_library_id: null,
    action_url: '',
    action_label: '',
    is_active: true,
    starts_at: '',
    expires_at: '',
});

const previewUrl = ref(null);
const showMediaPicker = ref(false);

const previewType = computed(() => form.type);
const hasMedia = computed(() => Boolean(form.media_library_id && previewUrl.value));
const acceptTypes = computed(() => (form.type === 'video' ? 'video/*' : 'image/*'));

watch(
    () => form.type,
    () => {
        form.media_library_id = null;
        previewUrl.value = null;
    },
);

const onMediaSelect = (item) => {
    if (!item) {
        return;
    }
    form.media_library_id = item.id;
    previewUrl.value = item.url || null;
};

const clearMedia = () => {
    form.media_library_id = null;
    previewUrl.value = null;
};

const submit = () => {
    form.post(route('marketing.stories.store'));
};
</script>

<template>
    <Head title="Create Story" />

    <AdminLayout title="Create Story">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <Link :href="route('marketing.stories.index')" class="text-sm text-brand-navy hover:text-brand-orange">← Stories</Link>
                <h1 class="mt-1 text-xl font-semibold text-brand-navy">Create story</h1>
            </div>
        </div>

        <form class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]" @submit.prevent="submit">
            <div class="space-y-6">
                <section class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Type</h2>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="rounded-xl border px-4 py-4 text-left transition"
                            :class="form.type === 'image' ? 'border-brand-teal bg-brand-teal/5' : 'border-gray-200'"
                            @click="form.type = 'image'"
                        >
                            <p class="text-sm font-semibold text-brand-navy">Image</p>
                            <p class="text-xs text-gray-500">JPG, PNG, WebP</p>
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border px-4 py-4 text-left transition"
                            :class="form.type === 'video' ? 'border-brand-teal bg-brand-teal/5' : 'border-gray-200'"
                            @click="form.type = 'video'"
                        >
                            <p class="text-sm font-semibold text-brand-navy">Video</p>
                            <p class="text-xs text-gray-500">MP4, WebM</p>
                        </button>
                    </div>
                </section>

                <section class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Media</h2>
                    <div class="mt-4 rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4">
                        <div v-if="hasMedia" class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-brand-navy">Selected from media library</p>
                                <p class="truncate text-xs text-gray-500">{{ previewUrl }}</p>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <button type="button" class="text-sm font-medium text-brand-orange" @click="showMediaPicker = true">Replace</button>
                                <button type="button" class="text-sm font-medium text-red-600" @click="clearMedia">Remove</button>
                            </div>
                        </div>
                        <button
                            v-else
                            type="button"
                            class="flex w-full flex-col items-center justify-center px-6 py-8 text-center"
                            @click="showMediaPicker = true"
                        >
                            <p class="text-sm font-medium text-brand-navy">Choose from media library</p>
                            <p class="mt-1 text-xs text-gray-500">Upload new files in Media Library if needed</p>
                        </button>
                    </div>
                    <InputError :message="form.errors.media_library_id" class="mt-2" />
                </section>

                <section class="admin-card space-y-4">
                    <h2 class="text-sm font-semibold text-brand-navy">Details</h2>
                    <div>
                        <InputLabel for="title" value="Title" />
                        <TextInput id="title" v-model="form.title" class="mt-1.5 block w-full" placeholder="Summer sale, New arrival…" />
                        <InputError :message="form.errors.title" class="mt-1" />
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Schedule</p>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="starts_at" value="Start date" />
                                <DateTimePicker id="starts_at" v-model="form.starts_at" placeholder="Pick start date & time" class="mt-1.5" />
                                <InputError :message="form.errors.starts_at" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="expires_at" value="End date" />
                                <DateTimePicker id="expires_at" v-model="form.expires_at" placeholder="Pick end date & time" class="mt-1.5" />
                                <InputError :message="form.errors.expires_at" class="mt-1" />
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-white p-4">
                        <ToggleSwitch v-model="form.is_active" label="Visibility" description="Inactive stories stay hidden." />
                    </div>
                    <div>
                        <InputLabel for="action_url" value="Action URL" />
                        <TextInput id="action_url" v-model="form.action_url" class="mt-1.5 block w-full" placeholder="https://" />
                        <InputError :message="form.errors.action_url" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="action_label" value="Action label" />
                        <TextInput id="action_label" v-model="form.action_label" class="mt-1.5 block w-full" placeholder="Shop now" />
                        <InputError :message="form.errors.action_label" class="mt-1" />
                    </div>
                </section>

                <div class="flex justify-end gap-3">
                    <Link :href="route('marketing.stories.index')">
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton :disabled="form.processing">Publish story</PrimaryButton>
                </div>
            </div>

            <StoryPreviewPanel
                :preview-url="previewUrl"
                :preview-type="previewType"
                :title="form.title"
                :action-label="form.action_label"
            />
        </form>

        <MediaPicker
            :show="showMediaPicker"
            :multiple="false"
            :accept="acceptTypes"
            title="Select story media"
            :selected-ids="form.media_library_id ? [form.media_library_id] : []"
            @close="showMediaPicker = false"
            @select="onMediaSelect"
        />
    </AdminLayout>
</template>
