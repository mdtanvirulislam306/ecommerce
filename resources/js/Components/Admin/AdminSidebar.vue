<script setup>
import NavIcon from '@/Components/Admin/NavIcon.vue';
import { isPathActive, moreModules, primaryModules, settingsModule } from '@/navigation/modules';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const emit = defineEmits(['navigate']);

const page = usePage();
const currentUrl = computed(() => page.url);
const enabledModules = computed(() => page.props.enabledModules ?? []);
const shopFlags = computed(() => page.props.shopFlags ?? {});
const isPlatformAdmin = computed(() => Boolean(page.props.auth?.user?.is_platform_admin));
const deniedPaths = computed(() => new Set(page.props.auth?.denied_paths ?? []));

const isPathDenied = (path) => deniedPaths.value.has((path || '').split('?')[0]);

/** Map sidebar keys to module.json codes */
const navKeyToModuleCode = {
    products: 'catalog',
    crm: 'crm',
    inventory: 'inventory',
    purchase: 'purchase',
    sales: 'sales',
    pos: 'pos',
    ecommerce: 'ecommerce',
    accounting: 'accounting',
    hrm: 'hrm',
    reports: 'reports',
    marketing: 'marketing',
    commerce: 'commerce',
    support: 'support',
    workflow: 'workflow',
    tasks: 'tasks',
    notifications: 'notifications',
    files: 'files',
    settings: 'settings',
    billing: 'billing',
};

const moduleEnabled = (navKey) => {
    const code = navKeyToModuleCode[navKey];
    if (code === null || code === undefined) return true;
    return enabledModules.value.includes(code);
};

const filterNavChildren = (mod) => {
    if (!mod?.children) return [];

    const flags = shopFlags.value;

    return mod.children
        .map((child) => {
            if (child.children) {
                const kids = filterNavChildren({ children: child.children, key: mod.key });
                return kids.length ? { ...child, children: kids } : null;
            }

            const path = child.path || '';

            if (path.startsWith('/platform') && !isPlatformAdmin.value) {
                return null;
            }

            if (path === '/admin/billing/plans' && !isPlatformAdmin.value) {
                return null;
            }

            if (isPathDenied(path)) {
                return null;
            }

            if (mod.key === 'commerce' && !flags.multi_price) {
                if (path.includes('/pricing/price-lists') || path.includes('/pricing/customer-groups') || path.includes('/pricing/quantity') || path.includes('/pricing/history')) {
                    return null;
                }
            }

            if (mod.key === 'inventory' && !flags.multi_warehouse) {
                if (path.includes('/warehouses') || path.includes('/stock-transfer')) {
                    return null;
                }
            }

            if (mod.key === 'settings' && !flags.multi_branch && path.includes('/branches')) {
                return null;
            }

            if ((mod.key === 'settings' || mod.key === 'inventory') && !flags.multi_warehouse && path.includes('/warehouses')) {
                return null;
            }

            return child;
        })
        .filter(Boolean);
};

const firstVisiblePath = (children) => {
    for (const child of children) {
        const path = child.children ? firstVisiblePath(child.children) : child.path;
        if (path) return path;
    }
    return null;
};

/** Modules the user can open, linked to their first permitted page instead of a default page they may be denied. */
const withLandingPath = (modules) =>
    modules
        .filter((m) => moduleEnabled(m.key))
        .map((m) => {
            const children = filterNavChildren(m);
            if (!children.length) return null;
            const landingPath = isPathDenied(m.defaultPath) ? firstVisiblePath(children) : m.defaultPath;
            return { ...m, defaultPath: landingPath ?? m.defaultPath };
        })
        .filter(Boolean);

const visiblePrimaryModules = computed(() => withLandingPath(primaryModules));

const visibleMoreModules = computed(() => withLandingPath(moreModules));

const visibleSettingsModule = computed(() => withLandingPath([settingsModule])[0] ?? null);

const filteredActiveChildren = computed(() => filterNavChildren(activeModuleData.value));


/** root = main menu | sub = more picker or module children */
const panel = ref('root');
const subView = ref(null);
const openedFrom = ref('root');

const activeModuleData = computed(() => {
    if (!subView.value || subView.value === 'more') return null;

    return [...primaryModules, ...moreModules, settingsModule].find(
        (m) => m.key === subView.value,
    );
});

const isModuleFromMore = (key) => moreModules.some((m) => m.key === key);

const syncFromUrl = () => {
    const url = page.url;

    if (url.startsWith('/admin/dashboard') || url === '/admin/profile') {
        panel.value = 'root';
        subView.value = null;
        return;
    }

    const match = url.match(/^\/admin\/([^/]+)/);
    if (!match) return;

    const key = match[1];
    const mod = [...primaryModules, ...moreModules, settingsModule].find((m) => m.key === key);

    if (mod) {
        panel.value = 'sub';
        subView.value = key;
        openedFrom.value = isModuleFromMore(key) ? 'more' : 'root';
    }
};

watch(() => page.url, syncFromUrl, { immediate: true });

const openMore = () => {
    panel.value = 'sub';
    subView.value = 'more';
    openedFrom.value = 'root';
};

const openModule = (mod, from = 'root') => {
    panel.value = 'sub';
    subView.value = mod.key;
    openedFrom.value = from;
    emit('navigate');
};

const goBack = () => {
    if (subView.value !== 'more' && openedFrom.value === 'more') {
        subView.value = 'more';
        return;
    }

    panel.value = 'root';
    subView.value = null;
};

const isActivePath = (path) => isPathActive(currentUrl.value, path);

const subTitle = computed(() => {
    if (subView.value === 'more') return 'More';
    return activeModuleData.value?.label ?? '';
});
</script>

<template>
    <aside class="flex h-full w-[min(100vw,18rem)] shrink-0 flex-col border-r border-gray-200 bg-white sm:w-72">
        <!-- Header -->
        <div class="flex h-14 shrink-0 items-center gap-3 border-b border-gray-100 px-4">
            <Link :href="route('dashboard')" class="flex items-center gap-3" @click="emit('navigate')">
                <img src="/images/logo.jpg" alt="Budget and Bazar" class="h-9 w-9 rounded object-cover" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-brand-navy">Budget & Bazar</p>
                    <p class="text-xs text-gray-400">Admin</p>
                </div>
            </Link>
        </div>

        <!-- Sliding panels -->
        <div class="relative flex-1 overflow-hidden">
            <div
                class="flex h-full w-[200%] transition-transform duration-300 ease-in-out"
                :class="panel === 'sub' ? '-translate-x-1/2' : 'translate-x-0'"
            >
                <!-- ROOT: main menu -->
                <nav class="flex h-full w-1/2 flex-col overflow-y-auto px-3 py-3">
                    <Link
                        :href="route('dashboard')"
                        class="mb-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                        :class="
                            currentUrl.startsWith('/admin/dashboard')
                                ? 'bg-brand-teal/15 text-brand-navy'
                                : 'text-gray-600 hover:bg-gray-100'
                        "
                        @click="emit('navigate')"
                    >
                        <NavIcon name="dashboard" class="text-brand-teal-dark" />
                        Dashboard
                    </Link>

                    <p class="mb-1 mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        Modules
                    </p>

                    <Link
                        v-for="mod in visiblePrimaryModules"
                        :key="mod.key"
                        :href="mod.defaultPath"
                        class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-brand-navy"
                        @click="openModule(mod, 'root')"
                    >
                        <NavIcon :name="mod.icon" class="text-gray-400" />
                        {{ mod.label }}
                        <svg class="ml-auto h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>

                    <button
                        type="button"
                        class="mb-0.5 mt-1 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-brand-navy"
                        @click="openMore"
                    >
                        <NavIcon name="more" class="text-gray-400" />
                        More
                        <svg class="ml-auto h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <div v-if="visibleSettingsModule" class="mt-4 border-t border-gray-100 pt-3">
                        <Link
                            :href="visibleSettingsModule.defaultPath"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-brand-navy"
                            @click="openModule(visibleSettingsModule, 'root')"
                        >
                            <NavIcon name="settings" class="text-gray-400" />
                            Settings
                            <svg class="ml-auto h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </nav>

                <!-- SUB: more picker OR module children -->
                <div class="flex h-full w-1/2 flex-col">
                    <div class="flex shrink-0 items-center gap-2 border-b border-gray-100 px-3 py-3">
                        <button
                            type="button"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100"
                            aria-label="Back"
                            @click="goBack"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <h2 class="truncate text-sm font-semibold text-brand-navy">{{ subTitle }}</h2>
                    </div>

                    <nav class="flex-1 overflow-y-auto px-3 py-3">
                        <!-- More module picker -->
                        <template v-if="subView === 'more'">
                            <Link
                                v-for="mod in visibleMoreModules"
                                :key="mod.key"
                                :href="mod.defaultPath"
                                class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100"
                                @click="openModule(mod, 'more')"
                            >
                                <NavIcon :name="mod.icon" class="text-gray-400" />
                                {{ mod.label }}
                                <svg class="ml-auto h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </template>

                        <!-- Module submenu -->
                        <template v-else-if="activeModuleData">
                            <template v-for="(entry, idx) in filteredActiveChildren" :key="idx">
                                <div v-if="entry.children" class="mb-3">
                                    <p class="mb-1 px-3 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                        {{ entry.label }}
                                    </p>
                                    <Link
                                        v-for="child in entry.children"
                                        :key="child.path"
                                        :href="child.path"
                                        class="mb-0.5 block rounded-lg px-3 py-2 text-sm transition-colors"
                                        :class="
                                            isActivePath(child.path)
                                                ? 'bg-brand-teal/15 font-medium text-brand-navy'
                                                : 'text-gray-600 hover:bg-gray-100'
                                        "
                                        @click="emit('navigate')"
                                    >
                                        {{ child.label }}
                                    </Link>
                                </div>
                                <Link
                                    v-else
                                    :href="entry.path"
                                    class="mb-0.5 block rounded-lg px-3 py-2.5 text-sm transition-colors"
                                    :class="
                                        isActivePath(entry.path)
                                            ? 'bg-brand-teal/15 font-medium text-brand-navy'
                                            : 'text-gray-600 hover:bg-gray-100'
                                    "
                                    @click="emit('navigate')"
                                >
                                    {{ entry.label }}
                                </Link>
                            </template>
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </aside>
</template>
