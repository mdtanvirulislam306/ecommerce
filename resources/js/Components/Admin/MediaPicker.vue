<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    multiple: { type: Boolean, default: true },
    accept: { type: String, default: 'image/*' },
    title: { type: String, default: 'Media gallery' },
    selectedIds: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'select']);

const items = ref([]);
const loading = ref(false);
const uploading = ref(false);
const search = ref('');
const selected = ref([]);
const error = ref('');
const fileInput = ref(null);
const lastClickedId = ref(null);
const loadedOnce = ref(false);

const selectedSet = computed(() => new Set(selected.value.map((i) => i.id)));
const selectedCount = computed(() => selected.value.length);

/** Prefer same-origin relative paths (Ziggy absolute URLs break on Laragon vs APP_URL). */
const pickerUrl = (query = '') => {
    const path = route('files.media-library.picker', {}, false);
    if (!query) return path;
    const sep = path.includes('?') ? '&' : '?';
    return `${path}${sep}q=${encodeURIComponent(query)}`;
};

const uploadUrl = () => route('files.media-library.picker-upload', {}, false);

const csrfHeaders = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    return {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(token ? { 'X-CSRF-TOKEN': token } : {}),
    };
};

const isImage = (item) => {
    if (item.mime?.startsWith('image/')) return true;
    return /\.(jpe?g|png|gif|webp|svg)$/i.test(item.name || item.path || item.url || '');
};

const syncPreselected = () => {
    if (!props.selectedIds?.length) return;
    const pre = items.value.filter((i) => props.selectedIds.includes(i.id));
    if (pre.length) selected.value = pre;
};

const load = async () => {
    loading.value = true;
    error.value = '';
    try {
        const res = await fetch(pickerUrl(search.value.trim()), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error('Failed to load media');
        const data = await res.json();
        items.value = data.items ?? [];
        syncPreselected();
        loadedOnce.value = true;
    } catch (e) {
        error.value = e.message || 'Could not load media library';
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.show,
    (show) => {
        if (!show) return;
        selected.value = [];
        lastClickedId.value = null;
        // Avoid resetting search (and double-fetch) when reopening with empty query.
        if (!loadedOnce.value || items.value.length === 0) {
            load();
        } else {
            syncPreselected();
        }
    },
);

let searchTimer = null;
watch(search, (value, oldValue) => {
    if (!props.show) return;
    if (value === oldValue) return;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(load, 300);
});

const toggle = (item, event = null) => {
    if (!props.multiple) {
        selected.value = [item];
        lastClickedId.value = item.id;
        return;
    }

    if (event?.shiftKey && lastClickedId.value != null) {
        const ids = items.value.map((i) => i.id);
        const from = ids.indexOf(lastClickedId.value);
        const to = ids.indexOf(item.id);
        if (from !== -1 && to !== -1) {
            const [a, b] = from < to ? [from, to] : [to, from];
            const range = items.value.slice(a, b + 1);
            const map = new Map(selected.value.map((i) => [i.id, i]));
            range.forEach((i) => map.set(i.id, i));
            selected.value = [...map.values()];
            lastClickedId.value = item.id;
            return;
        }
    }

    const idx = selected.value.findIndex((i) => i.id === item.id);
    if (idx === -1) selected.value = [...selected.value, item];
    else selected.value = selected.value.filter((i) => i.id !== item.id);
    lastClickedId.value = item.id;
};

const selectAllVisible = () => {
    if (!items.value.length) return;
    const map = new Map(selected.value.map((i) => [i.id, i]));
    items.value.forEach((i) => map.set(i.id, i));
    selected.value = [...map.values()];
};

const clearSelection = () => {
    selected.value = [];
};

const selectionIndex = (id) => {
    const idx = selected.value.findIndex((i) => i.id === id);
    return idx === -1 ? null : idx + 1;
};

const confirm = () => {
    emit('select', props.multiple ? selected.value : selected.value[0] ?? null);
    emit('close');
};

const onUpload = async (event) => {
    const files = [...(event.target.files || [])];
    if (!files.length) return;

    uploading.value = true;
    error.value = '';
    try {
        const body = new FormData();
        if (files.length === 1) {
            body.append('file', files[0]);
        } else {
            files.forEach((f) => body.append('files[]', f));
        }

        const res = await fetch(uploadUrl(), {
            method: 'POST',
            headers: csrfHeaders(),
            credentials: 'same-origin',
            body,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.message || data.errors?.file?.[0] || data.errors?.['files.0']?.[0] || 'Upload failed');
        }

        const uploaded = data.items ?? (data.item ? [data.item] : []);
        if (uploaded.length) {
            const ids = new Set(uploaded.map((i) => i.id));
            items.value = [...uploaded, ...items.value.filter((i) => !ids.has(i.id))];
            if (props.multiple) {
                const map = new Map(selected.value.map((i) => [i.id, i]));
                uploaded.forEach((i) => map.set(i.id, i));
                selected.value = [...map.values()];
            } else {
                selected.value = [uploaded[0]];
            }
            lastClickedId.value = uploaded[uploaded.length - 1]?.id ?? null;
            loadedOnce.value = true;
        }
    } catch (e) {
        error.value = e.message || 'Upload failed';
    } finally {
        uploading.value = false;
        if (fileInput.value) fileInput.value.value = '';
    }
};

const onImgError = (event) => {
    event.target.style.display = 'none';
};
</script>

<template>
    <Modal :show="show" max-width="5xl" @close="emit('close')">
        <div class="flex max-h-[85vh] flex-col p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-brand-navy">{{ title }}</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ multiple ? 'Click to multi-select · Shift+click for range · Upload many at once.' : 'Select one image.' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <input
                        ref="fileInput"
                        type="file"
                        class="hidden"
                        :accept="accept"
                        multiple
                        @change="onUpload"
                    />
                    <SecondaryButton type="button" :disabled="uploading" @click="fileInput?.click()">
                        {{ uploading ? 'Uploading…' : 'Upload images' }}
                    </SecondaryButton>
                    <SecondaryButton type="button" :disabled="loading" @click="load">Refresh</SecondaryButton>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search media…"
                    class="min-w-[12rem] flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                />
                <SecondaryButton v-if="multiple" type="button" :disabled="!items.length" @click="selectAllVisible">
                    Select all
                </SecondaryButton>
                <SecondaryButton v-if="selectedCount" type="button" @click="clearSelection">Clear</SecondaryButton>
            </div>

            <p v-if="error" class="mt-3 text-sm text-red-600">{{ error }}</p>

            <div class="relative mt-4 min-h-[12rem] flex-1 overflow-y-auto">
                <div
                    v-if="loading"
                    class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 text-sm text-gray-500"
                >
                    Loading gallery…
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    <button
                        v-for="item in items"
                        :key="item.id"
                        type="button"
                        class="group relative overflow-hidden rounded-xl border-2 text-left transition focus:outline-none focus:ring-2 focus:ring-brand-teal/40"
                        :class="
                            selectedSet.has(item.id)
                                ? 'border-brand-teal ring-1 ring-brand-teal/40'
                                : 'border-gray-100 hover:border-gray-300'
                        "
                        @click="toggle(item, $event)"
                    >
                        <div class="aspect-square bg-gray-50">
                            <img
                                v-if="isImage(item)"
                                :src="item.url"
                                :alt="item.name"
                                class="h-full w-full object-cover"
                                loading="lazy"
                                decoding="async"
                                @error="onImgError"
                            />
                            <div v-else class="flex h-full items-center justify-center p-3 text-center text-xs text-gray-500">
                                {{ item.name }}
                            </div>
                        </div>
                        <p class="truncate px-2 py-1.5 text-[11px] text-gray-600">{{ item.name }}</p>
                        <span
                            v-if="selectedSet.has(item.id)"
                            class="absolute right-2 top-2 flex h-6 min-w-6 items-center justify-center rounded-full bg-brand-teal px-1.5 text-[11px] font-semibold text-white shadow"
                        >
                            {{ multiple ? selectionIndex(item.id) : '✓' }}
                        </span>
                    </button>
                    <p v-if="!loading && !items.length" class="col-span-full py-12 text-center text-sm text-gray-500">
                        No media yet. Upload your first images.
                    </p>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                <p class="text-xs text-gray-500">
                    <span class="font-medium text-brand-navy">{{ selectedCount }}</span>
                    selected
                </p>
                <div class="flex gap-2">
                    <SecondaryButton type="button" @click="emit('close')">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="!selectedCount" @click="confirm">
                        Use selected
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </Modal>
</template>
