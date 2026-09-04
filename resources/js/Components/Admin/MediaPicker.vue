<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    multiple: { type: Boolean, default: true },
    accept: { type: String, default: 'image/*' },
    title: { type: String, default: 'Select from media library' },
});

const emit = defineEmits(['close', 'select']);

const items = ref([]);
const loading = ref(false);
const uploading = ref(false);
const search = ref('');
const selected = ref([]);
const error = ref('');
const fileInput = ref(null);

const selectedSet = computed(() => new Set(selected.value.map((i) => i.id)));

const load = async () => {
    loading.value = true;
    error.value = '';
    try {
        const url = new URL(route('files.media-library.picker'), window.location.origin);
        if (search.value.trim()) url.searchParams.set('q', search.value.trim());
        const res = await fetch(url.toString(), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error('Failed to load media');
        const data = await res.json();
        items.value = data.items ?? [];
    } catch (e) {
        error.value = e.message || 'Could not load media library';
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.show,
    (show) => {
        if (show) {
            selected.value = [];
            search.value = '';
            load();
        }
    },
);

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(load, 250);
});

const toggle = (item) => {
    if (props.multiple) {
        const idx = selected.value.findIndex((i) => i.id === item.id);
        if (idx === -1) selected.value = [...selected.value, item];
        else selected.value = selected.value.filter((i) => i.id !== item.id);
        return;
    }
    selected.value = [item];
};

const confirm = () => {
    emit('select', props.multiple ? selected.value : selected.value[0] ?? null);
    emit('close');
};

const onUpload = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    uploading.value = true;
    error.value = '';
    try {
        const body = new FormData();
        body.append('file', file);
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const res = await fetch(route('files.media-library.picker-upload'), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            credentials: 'same-origin',
            body,
        });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw new Error(data.message || 'Upload failed');
        }
        const data = await res.json();
        if (data.item) {
            items.value = [data.item, ...items.value.filter((i) => i.id !== data.item.id)];
            toggle(data.item);
        }
    } catch (e) {
        error.value = e.message || 'Upload failed';
    } finally {
        uploading.value = false;
        if (fileInput.value) fileInput.value.value = '';
    }
};
</script>

<template>
    <Modal :show="show" max-width="3xl" @close="emit('close')">
        <div class="p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-brand-navy">{{ title }}</h2>
                    <p class="mt-1 text-sm text-gray-500">Pick existing files or upload into the media library.</p>
                </div>
                <div class="flex gap-2">
                    <input ref="fileInput" type="file" class="hidden" :accept="accept" @change="onUpload" />
                    <SecondaryButton type="button" :disabled="uploading" @click="fileInput?.click()">
                        {{ uploading ? 'Uploading…' : 'Upload' }}
                    </SecondaryButton>
                </div>
            </div>

            <div class="mt-4">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search media…"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                />
            </div>

            <p v-if="error" class="mt-3 text-sm text-red-600">{{ error }}</p>
            <p v-else-if="loading" class="mt-6 text-center text-sm text-gray-500">Loading…</p>

            <div v-else class="mt-4 grid max-h-[22rem] grid-cols-2 gap-3 overflow-y-auto sm:grid-cols-3 md:grid-cols-4">
                <button
                    v-for="item in items"
                    :key="item.id"
                    type="button"
                    class="group relative overflow-hidden rounded-xl border-2 text-left transition"
                    :class="
                        selectedSet.has(item.id)
                            ? 'border-brand-teal ring-1 ring-brand-teal/40'
                            : 'border-gray-100 hover:border-gray-300'
                    "
                    @click="toggle(item)"
                >
                    <div class="aspect-square bg-gray-50">
                        <img
                            v-if="item.mime?.startsWith('image/')"
                            :src="item.url"
                            :alt="item.name"
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="flex h-full items-center justify-center p-3 text-center text-xs text-gray-500">
                            {{ item.name }}
                        </div>
                    </div>
                    <p class="truncate px-2 py-1.5 text-[11px] text-gray-600">{{ item.name }}</p>
                    <span
                        v-if="selectedSet.has(item.id)"
                        class="absolute right-2 top-2 rounded-full bg-brand-teal px-1.5 py-0.5 text-[10px] font-semibold text-white"
                    >
                        ✓
                    </span>
                </button>
                <p v-if="!items.length" class="col-span-full py-10 text-center text-sm text-gray-500">
                    No media yet. Upload your first file.
                </p>
            </div>

            <div class="mt-5 flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                <p class="text-xs text-gray-500">{{ selected.length }} selected</p>
                <div class="flex gap-2">
                    <SecondaryButton type="button" @click="emit('close')">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="!selected.length" @click="confirm">
                        Use selected
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </Modal>
</template>
