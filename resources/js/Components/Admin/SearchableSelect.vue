<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Select…' },
    searchPlaceholder: { type: String, default: 'Search…' },
    labelKey: { type: String, default: 'name' },
    valueKey: { type: String, default: 'id' },
    allowClear: { type: Boolean, default: true },
    disabled: { type: Boolean, default: false },
    /** Show + button that emits "create" */
    creatable: { type: Boolean, default: false },
    createLabel: { type: String, default: 'Add new' },
});

const model = defineModel({ default: null });
const emit = defineEmits(['create']);

const open = ref(false);
const query = ref('');
const root = ref(null);
const trigger = ref(null);
const panel = ref(null);
const searchInput = ref(null);
const teleportTo = ref('body');
const panelStyle = ref({});

const selectedLabel = computed(() => {
    if (model.value === null || model.value === '' || model.value === undefined) {
        return '';
    }
    const match = props.options.find((o) => String(o[props.valueKey]) === String(model.value));
    return match ? match[props.labelKey] : '';
});

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) {
        return props.options;
    }
    return props.options.filter((o) => String(o[props.labelKey] ?? '').toLowerCase().includes(q));
});

const placePanel = () => {
    if (!trigger.value) {
        return;
    }

    const rect = trigger.value.getBoundingClientRect();
    const gap = 6;
    const viewportPadding = 12;
    const panelHeight = 260;
    let top = rect.bottom + gap;
    let left = rect.left;
    const width = Math.max(rect.width, 200);

    if (left + width > window.innerWidth - viewportPadding) {
        left = Math.max(viewportPadding, rect.right - width);
    }

    if (top + panelHeight > window.innerHeight - viewportPadding && rect.top > panelHeight + gap) {
        top = rect.top - panelHeight - gap;
    }

    panelStyle.value = {
        position: 'fixed',
        top: `${Math.max(viewportPadding, top)}px`,
        left: `${Math.max(viewportPadding, left)}px`,
        width: `${width}px`,
        zIndex: 100,
    };
};

const resolveTeleport = () => {
    teleportTo.value = root.value?.closest('dialog') || 'body';
};

const select = (option) => {
    model.value = option[props.valueKey];
    open.value = false;
    query.value = '';
};

const clear = () => {
    model.value = null;
    open.value = false;
};

const toggle = async () => {
    if (props.disabled) {
        return;
    }

    open.value = !open.value;

    if (open.value) {
        query.value = '';
        resolveTeleport();
        await nextTick();
        placePanel();
        searchInput.value?.focus();
    }
};

const onOutside = (e) => {
    const inRoot = root.value?.contains(e.target);
    const inPanel = panel.value?.contains(e.target);
    if (!inRoot && !inPanel) {
        open.value = false;
    }
};

const onReposition = () => {
    if (open.value) {
        placePanel();
    }
};

onMounted(() => {
    document.addEventListener('mousedown', onOutside);
    window.addEventListener('resize', onReposition);
    window.addEventListener('scroll', onReposition, true);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', onOutside);
    window.removeEventListener('resize', onReposition);
    window.removeEventListener('scroll', onReposition, true);
});

watch(open, (v) => {
    if (!v) {
        query.value = '';
    }
});
</script>

<template>
    <div ref="root" class="flex items-start gap-2">
        <div class="relative min-w-0 flex-1">
            <button
                ref="trigger"
                type="button"
                class="flex h-[38px] w-full items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-left text-sm shadow-sm transition hover:border-gray-400 focus:border-brand-teal focus:outline-none focus:ring-1 focus:ring-brand-teal disabled:opacity-50"
                :disabled="disabled"
                @click="toggle"
            >
                <span class="truncate" :class="selectedLabel ? 'text-brand-navy' : 'text-gray-400'">
                    {{ selectedLabel || placeholder }}
                </span>
                <svg class="ml-auto h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <Teleport :to="teleportTo">
                <div
                    v-if="open"
                    ref="panel"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl ring-1 ring-black/5"
                    :style="panelStyle"
                >
                    <div class="border-b border-gray-100 p-2">
                        <input
                            ref="searchInput"
                            v-model="query"
                            type="search"
                            :placeholder="searchPlaceholder"
                            class="w-full rounded-md border-gray-200 text-sm focus:border-brand-teal focus:ring-brand-teal"
                            @click.stop
                        />
                    </div>
                    <ul class="max-h-52 overflow-y-auto py-1">
                        <li v-if="allowClear">
                            <button
                                type="button"
                                class="w-full px-3 py-2 text-left text-sm text-gray-500 hover:bg-gray-50"
                                @click="clear"
                            >
                                None
                            </button>
                        </li>
                        <li v-for="opt in filtered" :key="opt[valueKey]">
                            <button
                                type="button"
                                class="flex w-full items-center px-3 py-2 text-left text-sm hover:bg-brand-teal/10"
                                :class="String(model) === String(opt[valueKey]) ? 'bg-brand-teal/10 font-medium text-brand-navy' : 'text-gray-700'"
                                @click="select(opt)"
                            >
                                {{ opt[labelKey] }}
                            </button>
                        </li>
                        <li v-if="!filtered.length" class="px-3 py-4 text-center text-xs text-gray-400">No matches</li>
                    </ul>
                </div>
            </Teleport>
        </div>

        <button
            v-if="creatable"
            type="button"
            class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-lg border border-brand-teal/40 bg-brand-teal/10 text-brand-navy transition hover:bg-brand-teal/20"
            :title="createLabel"
            @click="emit('create')"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
        </button>
    </div>
</template>
