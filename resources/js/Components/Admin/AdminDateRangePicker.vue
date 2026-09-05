<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    from: { type: String, default: '' },
    to: { type: String, default: '' },
    placeholder: { type: String, default: 'Date range' },
});

const emit = defineEmits(['update']);

const open = ref(false);
const root = ref(null);
const panel = ref(null);
const trigger = ref(null);
const viewDate = ref(new Date());
const hoverDate = ref(null);
const draftFrom = ref(props.from || '');
const draftTo = ref(props.to || '');
const pickingEnd = ref(false);
const panelStyle = ref({});

const weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
const pad = (n) => String(n).padStart(2, '0');

const toYmd = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

const parseYmd = (value) => {
    if (!value) {
        return null;
    }
    const [year, month, day] = value.split('-').map(Number);
    if (!year || !month || !day) {
        return null;
    }
    return new Date(year, month - 1, day);
};

const formatShort = (value) => {
    const date = parseYmd(value);
    if (!date) {
        return '';
    }
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
};

const displayValue = computed(() => {
    if (props.from && props.to) {
        return `${formatShort(props.from)} – ${formatShort(props.to)}`;
    }
    if (props.from) {
        return `From ${formatShort(props.from)}`;
    }
    if (props.to) {
        return `Until ${formatShort(props.to)}`;
    }
    return '';
});

const monthLabel = computed(() =>
    viewDate.value.toLocaleString(undefined, { month: 'long', year: 'numeric' }),
);

const rangeHint = computed(() => {
    if (draftFrom.value && draftTo.value) {
        return `${formatShort(draftFrom.value)} – ${formatShort(draftTo.value)}`;
    }
    if (draftFrom.value) {
        return `${formatShort(draftFrom.value)} → …`;
    }
    return 'Pick a start date';
});

const calendarDays = computed(() => {
    const year = viewDate.value.getFullYear();
    const month = viewDate.value.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();
    const cells = [];

    for (let i = firstDay - 1; i >= 0; i -= 1) {
        cells.push({ day: daysInPrevMonth - i, monthOffset: -1, key: `p-${daysInPrevMonth - i}` });
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

const cellDate = (cell) => {
    const year = viewDate.value.getFullYear();
    const month = viewDate.value.getMonth() + cell.monthOffset;
    return new Date(year, month, cell.day);
};

const start = computed(() => parseYmd(draftFrom.value));
const end = computed(() => parseYmd(draftTo.value || (pickingEnd.value && hoverDate.value ? toYmd(hoverDate.value) : '')));

const isSameDay = (a, b) =>
    a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();

const isInRange = (cell) => {
    const date = cellDate(cell);
    const from = start.value;
    const to = end.value || hoverDate.value;
    if (!from || !to) {
        return false;
    }
    const min = from <= to ? from : to;
    const max = from <= to ? to : from;
    return date >= min && date <= max;
};

const isEdge = (cell) => {
    const date = cellDate(cell);
    return isSameDay(date, start.value) || isSameDay(date, end.value);
};

const isToday = (cell) => isSameDay(cellDate(cell), new Date());

const placePanel = () => {
    if (!trigger.value) {
        return;
    }

    const rect = trigger.value.getBoundingClientRect();
    const panelWidth = 320;
    const gap = 8;
    const viewportPadding = 12;
    let left = rect.left;
    let top = rect.bottom + gap;

    if (left + panelWidth > window.innerWidth - viewportPadding) {
        left = Math.max(viewportPadding, rect.right - panelWidth);
    }

    const estimatedHeight = 380;
    if (top + estimatedHeight > window.innerHeight - viewportPadding && rect.top > estimatedHeight) {
        top = rect.top - estimatedHeight - gap;
    }

    panelStyle.value = {
        position: 'fixed',
        top: `${Math.max(viewportPadding, top)}px`,
        left: `${left}px`,
        width: `${panelWidth}px`,
        zIndex: 80,
    };
};

const apply = (from, to) => {
    emit('update', { from: from || '', to: to || '' });
};

const selectDay = (cell) => {
    const ymd = toYmd(cellDate(cell));

    if (!pickingEnd.value || !draftFrom.value) {
        draftFrom.value = ymd;
        draftTo.value = '';
        pickingEnd.value = true;
        return;
    }

    let from = draftFrom.value;
    let to = ymd;
    if (from > to) {
        [from, to] = [to, from];
    }
    draftFrom.value = from;
    draftTo.value = to;
    pickingEnd.value = false;
    open.value = false;
    apply(from, to);
};

const preset = (daysBack, daysForward = 0) => {
    const startDate = new Date();
    startDate.setHours(0, 0, 0, 0);
    startDate.setDate(startDate.getDate() - daysBack);
    const endDate = new Date();
    endDate.setHours(0, 0, 0, 0);
    endDate.setDate(endDate.getDate() + daysForward);
    const from = toYmd(startDate);
    const to = toYmd(endDate);
    draftFrom.value = from;
    draftTo.value = to;
    pickingEnd.value = false;
    open.value = false;
    apply(from, to);
};

const thisMonth = () => {
    const now = new Date();
    const from = toYmd(new Date(now.getFullYear(), now.getMonth(), 1));
    const to = toYmd(new Date(now.getFullYear(), now.getMonth() + 1, 0));
    draftFrom.value = from;
    draftTo.value = to;
    pickingEnd.value = false;
    open.value = false;
    apply(from, to);
};

const clear = () => {
    draftFrom.value = '';
    draftTo.value = '';
    pickingEnd.value = false;
    open.value = false;
    apply('', '');
};

const toggle = async () => {
    open.value = !open.value;
    if (open.value) {
        draftFrom.value = props.from || '';
        draftTo.value = props.to || '';
        pickingEnd.value = false;
        viewDate.value = parseYmd(props.from) || new Date();
        await nextTick();
        placePanel();
    }
};

const onClickOutside = (event) => {
    const inTrigger = root.value?.contains(event.target);
    const inPanel = panel.value?.contains(event.target);
    if (!inTrigger && !inPanel) {
        open.value = false;
    }
};

const onReposition = () => {
    if (open.value) {
        placePanel();
    }
};

watch(
    () => [props.from, props.to],
    ([from, to]) => {
        draftFrom.value = from || '';
        draftTo.value = to || '';
    },
);

onMounted(() => {
    document.addEventListener('mousedown', onClickOutside);
    window.addEventListener('resize', onReposition);
    window.addEventListener('scroll', onReposition, true);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', onClickOutside);
    window.removeEventListener('resize', onReposition);
    window.removeEventListener('scroll', onReposition, true);
});
</script>

<template>
    <div ref="root" class="relative inline-flex">
        <button
            ref="trigger"
            type="button"
            class="admin-date-range"
            :class="displayValue ? 'admin-date-range--filled' : ''"
            @click="toggle"
        >
            <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span class="min-w-0 flex-1 truncate">{{ displayValue || placeholder }}</span>
            <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <Teleport to="body">
            <div
                v-if="open"
                ref="panel"
                class="admin-date-range__panel"
                :style="panelStyle"
            >
                <div class="admin-date-range__presets">
                    <button type="button" class="admin-date-range__preset" @click="preset(0)">Today</button>
                    <button type="button" class="admin-date-range__preset" @click="preset(6)">Last 7 days</button>
                    <button type="button" class="admin-date-range__preset" @click="preset(29)">Last 30 days</button>
                    <button type="button" class="admin-date-range__preset" @click="thisMonth">This month</button>
                </div>

                <div class="admin-date-range__calendar">
                    <div class="flex items-center justify-between">
                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100"
                            @click="viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() - 1, 1)"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <p class="text-sm font-semibold text-brand-navy">{{ monthLabel }}</p>
                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100"
                            @click="viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 1)"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-3 grid grid-cols-7 gap-0.5 text-center text-[11px] font-medium text-gray-400">
                        <span v-for="day in weekdays" :key="day" class="py-1">{{ day }}</span>
                    </div>

                    <div class="mt-0.5 grid grid-cols-7 gap-0.5">
                        <button
                            v-for="cell in calendarDays"
                            :key="cell.key"
                            type="button"
                            class="admin-date-range__day"
                            :class="{
                                'admin-date-range__day--muted': cell.monthOffset !== 0,
                                'admin-date-range__day--range': isInRange(cell) && !isEdge(cell),
                                'admin-date-range__day--edge': isEdge(cell),
                                'admin-date-range__day--today': !isEdge(cell) && isToday(cell),
                                'admin-date-range__day--start': isSameDay(cellDate(cell), start),
                                'admin-date-range__day--end': isSameDay(cellDate(cell), end),
                            }"
                            @mouseenter="hoverDate = cellDate(cell)"
                            @click="selectDay(cell)"
                        >
                            {{ cell.day }}
                        </button>
                    </div>
                </div>

                <div class="admin-date-range__footer">
                    <p class="truncate text-[11px] text-gray-500">{{ rangeHint }}</p>
                    <button type="button" class="text-xs font-medium text-brand-orange hover:text-brand-orange-dark" @click="clear">
                        Clear
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>
