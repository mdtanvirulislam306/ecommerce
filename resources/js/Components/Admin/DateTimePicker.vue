<script setup>
import { computed, onMounted, onUnmounted, ref, watch, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

const attrs = useAttrs();

const props = defineProps({
    id: { type: String, default: '' },
    placeholder: { type: String, default: 'Select date & time' },
    /** 'datetime' | 'date' */
    mode: { type: String, default: 'datetime' },
});

const model = defineModel({ type: String, default: '' });

const open = ref(false);
const root = ref(null);
const viewDate = ref(new Date());
const hour = ref(0);
const minute = ref(0);
const selectedDate = ref(null);

const weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

const pad = (n) => String(n).padStart(2, '0');

const toModelValue = (date, h, m) => {
    const ymd = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    if (props.mode === 'date') {
        return ymd;
    }
    return `${ymd}T${pad(h)}:${pad(m)}`;
};

const parseModel = (value) => {
    if (!value) {
        return null;
    }

    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const syncFromModel = () => {
    const parsed = parseModel(model.value);

    if (!parsed) {
        selectedDate.value = null;
        return;
    }

    selectedDate.value = new Date(parsed.getFullYear(), parsed.getMonth(), parsed.getDate());
    hour.value = parsed.getHours();
    minute.value = parsed.getMinutes();
    viewDate.value = new Date(selectedDate.value);
};

watch(() => model.value, syncFromModel, { immediate: true });

const displayValue = computed(() => {
    const parsed = parseModel(model.value);
    if (!parsed) {
        return '';
    }

    if (props.mode === 'date') {
        return parsed.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    }

    return parsed.toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
});

const monthLabel = computed(() =>
    viewDate.value.toLocaleString(undefined, { month: 'long', year: 'numeric' }),
);

const calendarDays = computed(() => {
    const year = viewDate.value.getFullYear();
    const month = viewDate.value.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    const cells = [];

    for (let i = firstDay - 1; i >= 0; i -= 1) {
        const day = daysInPrevMonth - i;
        cells.push({ day, monthOffset: -1, key: `p-${day}` });
    }

    for (let day = 1; day <= daysInMonth; day += 1) {
        cells.push({ day, monthOffset: 0, key: `c-${day}` });
    }

    while (cells.length % 7 !== 0) {
        const day = cells.length - firstDay - daysInMonth + 1;
        cells.push({ day, monthOffset: 1, key: `n-${day}` });
    }

    return cells;
});

const isSelected = (cell) => {
    if (!selectedDate.value) {
        return false;
    }

    const year = viewDate.value.getFullYear();
    const month = viewDate.value.getMonth() + cell.monthOffset;
    const date = new Date(year, month, cell.day);

    return (
        selectedDate.value.getFullYear() === date.getFullYear() &&
        selectedDate.value.getMonth() === date.getMonth() &&
        selectedDate.value.getDate() === date.getDate()
    );
};

const isToday = (cell) => {
    const today = new Date();
    const year = viewDate.value.getFullYear();
    const month = viewDate.value.getMonth() + cell.monthOffset;

    return (
        today.getFullYear() === year &&
        today.getMonth() === month &&
        today.getDate() === cell.day
    );
};

const applySelection = () => {
    if (!selectedDate.value) {
        return;
    }

    model.value = toModelValue(selectedDate.value, hour.value, minute.value);
};

const selectDay = (cell) => {
    const year = viewDate.value.getFullYear();
    const month = viewDate.value.getMonth() + cell.monthOffset;
    selectedDate.value = new Date(year, month, cell.day);
    applySelection();
};

const prevMonth = () => {
    viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() - 1, 1);
};

const nextMonth = () => {
    viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() + 1, 1);
};

const setToday = () => {
    const now = new Date();
    selectedDate.value = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    hour.value = now.getHours();
    minute.value = now.getMinutes();
    viewDate.value = new Date(selectedDate.value);
    applySelection();
};

const clear = () => {
    model.value = '';
    selectedDate.value = null;
    open.value = false;
};

watch([hour, minute], () => {
    if (selectedDate.value) {
        applySelection();
    }
});

const toggle = () => {
    open.value = !open.value;
    if (open.value) {
        syncFromModel();
        if (!selectedDate.value) {
            viewDate.value = new Date();
        }
    }
};

const onClickOutside = (e) => {
    if (root.value && !root.value.contains(e.target)) {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('mousedown', onClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside));
</script>

<template>
    <div ref="root" class="relative" :class="attrs.class">
        <button
            :id="id"
            type="button"
            class="flex w-full items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm transition-colors hover:border-gray-400 focus:border-brand-teal focus:outline-none focus:ring-1 focus:ring-brand-teal"
            @click="toggle"
        >
            <span :class="displayValue ? 'text-brand-navy' : 'text-gray-400'">
                {{ displayValue || placeholder }}
            </span>
            <svg
                class="ml-auto h-4 w-4 shrink-0 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
            </svg>
        </button>

        <div
            v-if="open"
            class="absolute left-0 z-50 mt-2 w-[min(100vw-2rem,280px)] rounded-xl border border-gray-200 bg-white p-3 shadow-lg ring-1 ring-black/5"
        >
            <div class="flex items-center justify-between">
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100"
                    @click="prevMonth"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <p class="text-sm font-semibold text-brand-navy">{{ monthLabel }}</p>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100"
                    @click="nextMonth"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <div class="mt-2 grid grid-cols-7 gap-1 text-center text-[11px] font-medium text-gray-400">
                <span v-for="day in weekdays" :key="day">{{ day }}</span>
            </div>

            <div class="mt-1 grid grid-cols-7 gap-1">
                <button
                    v-for="cell in calendarDays"
                    :key="cell.key"
                    type="button"
                    class="h-8 rounded-lg text-xs transition-colors"
                    :class="[
                        cell.monthOffset !== 0 ? 'text-gray-300' : 'text-brand-navy',
                        isSelected(cell)
                            ? 'bg-brand-teal font-semibold text-white'
                            : isToday(cell)
                              ? 'bg-brand-teal/10 font-medium text-brand-teal-dark'
                              : 'hover:bg-gray-100',
                    ]"
                    @click="selectDay(cell)"
                >
                    {{ cell.day }}
                </button>
            </div>

            <div v-if="mode !== 'date'" class="mt-3 flex items-center gap-2 border-t border-gray-100 pt-3">
                <label class="text-xs font-medium text-gray-500">Time</label>
                <select
                    v-model.number="hour"
                    class="rounded-md border-gray-300 py-1.5 pl-2 pr-7 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                >
                    <option v-for="h in 24" :key="h" :value="h - 1">
                        {{ pad(h - 1) }}
                    </option>
                </select>
                <span class="text-gray-400">:</span>
                <select
                    v-model.number="minute"
                    class="rounded-md border-gray-300 py-1.5 pl-2 pr-7 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                >
                    <option v-for="m in 60" :key="m" :value="m - 1">
                        {{ pad(m - 1) }}
                    </option>
                </select>
            </div>

            <div class="mt-3 flex items-center justify-between gap-2">
                <button
                    type="button"
                    class="text-xs font-medium text-brand-orange hover:text-brand-orange-dark"
                    @click="setToday"
                >
                    Today
                </button>
                <button
                    type="button"
                    class="text-xs font-medium text-gray-500 hover:text-gray-700"
                    @click="clear"
                >
                    Clear
                </button>
            </div>
        </div>
    </div>
</template>
