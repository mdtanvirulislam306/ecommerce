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
import { computed, ref } from 'vue';

const props = defineProps({
    story: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    title: props.story.title ?? '',
    type: props.story.type,
    media_library_id: props.story.media_library_id || null,
    action_url: props.story.action_url ?? '',
    action_label: props.story.action_label ?? '',
    is_active: props.story.is_active,
    starts_at: props.story.starts_at ?? '',
    expires_at: props.story.expires_at ?? '',
});

const previewUrl = ref(props.story.media_url);
const showMediaPicker = ref(false);

const previewType = computed(() => form.type);
const acceptTypes = computed(() => (form.type === 'video' ? 'video/*' : 'image/*'));

const onMediaSelect = (item) => {
    if (!item) {
        return;
    }
    form.media_library_id = item.id;
    previewUrl.value = item.url || props.story.media_url;
};

const submit = () => {
    form.put(route('marketing.stories.update', props.story.id));
};
</script>

<template>
    <Head title="Edit Story" />

    <AdminLayout title="Edit Story">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Update story details, schedule, or media.</p>
            <Link :href="route('marketing.stories.index')" class="text-sm font-medium text-brand-navy hover:text-brand-orange">
                ← Back to stories
            </Link>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,800px)_1fr]">
            <form class="min-w-0 space-y-5" @submit.prevent="submit">
                <section class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Story format</h2>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="rounded-xl border-2 p-4 text-left transition-all"
                            :class="form.type === 'image' ? 'border-brand-teal bg-brand-teal/10' : 'border-gray-200'"
                            @click="form.type = 'image'"
                        >
                            <p class="text-sm font-semibold text-brand-navy">Image</p>
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border-2 p-4 text-left transition-all"
                            :class="form.type === 'video' ? 'border-brand-teal bg-brand-teal/10' : 'border-gray-200'"
                            @click="form.type = 'video'"
                        >
                            <p class="text-sm font-semibold text-brand-navy">Video</p>
                        </button>
                    </div>
                </section>

                <section class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Media</h2>
                    <div class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white ring-1 ring-gray-100">
                                <img v-if="previewUrl && previewType === 'image'" :src="previewUrl" alt="" class="h-full w-full object-cover" />
                                <span v-else class="text-[10px] text-gray-400">{{ form.media_library_id || previewUrl ? 'Ready' : 'None' }}</span>
                            </div>
                            <p class="truncate text-sm text-gray-600">
                                {{ form.media_library_id ? 'Selected from media library' : (previewUrl ? 'Current file attached' : 'No media selected') }}
                            </p>
                        </div>
                        <button type="button" class="shrink-0 text-sm font-medium text-brand-orange" @click="showMediaPicker = true">
                            Choose from library
                        </button>
                    </div>
                    <InputError :message="form.errors.media_library_id" class="mt-2" />
                </section>

                <section class="admin-card space-y-4">
                    <h2 class="text-sm font-semibold text-brand-navy">Details</h2>
                    <div>
                        <InputLabel for="title" value="Title" />
                        <TextInput id="title" v-model="form.title" class="mt-1.5 block w-full" />
                        <InputError :message="form.errors.title" class="mt-1" />
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="starts_at" value="Start date" />
                                <DateTimePicker id="starts_at" v-model="form.starts_at" class="mt-1.5" />
                            </div>
                            <div>
                                <InputLabel for="expires_at" value="End date" />
                                <DateTimePicker id="expires_at" v-model="form.expires_at" class="mt-1.5" />
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-white p-4">
                        <ToggleSwitch v-model="form.is_active" label="Visibility" />
                    </div>
                </section>

                <section class="admin-card space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="action_url" value="URL" />
                            <TextInput id="action_url" v-model="form.action_url" type="url" class="mt-1.5 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="action_label" value="Button text" />
                            <TextInput id="action_label" v-model="form.action_label" class="mt-1.5 block w-full" />
                        </div>
                    </div>
                </section>

                <div class="flex flex-wrap items-center gap-3">
                    <PrimaryButton :disabled="form.processing">{{ form.processing ? 'Saving…' : 'Save changes' }}</PrimaryButton>
                    <Link :href="route('marketing.stories.index')">
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                </div>
            </form>

            <div class="hidden min-h-[560px] flex-col items-center justify-start rounded-2xl border border-gray-200 bg-gradient-to-br from-slate-50 via-white to-brand-teal/5 p-8 shadow-sm xl:flex">
                <StoryPreviewPanel
                    :preview-url="previewUrl"
                    :preview-type="previewType"
                    :title="form.title"
                    :action-label="form.action_label"
                />
            </div>
        </div>

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
