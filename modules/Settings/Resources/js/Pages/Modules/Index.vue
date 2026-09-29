<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { moduleGatePath } from '@/navigation/modules';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    modules: { type: Array, default: () => [] },
});

const page = usePage();

const rows = computed(() => {
    if (props.modules.length > 0) {
        return [...props.modules].sort((left, right) => {
            if (left.enabled !== right.enabled) {
                return left.enabled ? -1 : 1;
            }

            return String(left.name).localeCompare(String(right.name));
        });
    }

    return (page.props.enabledModules ?? []).map((code) => ({
        code,
        name: code,
        description: '',
        is_core: false,
        enabled: true,
    }));
});

const enabledCount = computed(() => rows.value.filter((mod) => mod.enabled).length);
const lockedCount = computed(() => rows.value.length - enabledCount.value);

function upgradeHref(mod) {
    if (mod.enabled) {
        return null;
    }

    return moduleGatePath(mod.code);
}
</script>

<template>
    <Head title="Modules" />

    <AdminLayout title="Modules">
        <div class="max-w-4xl space-y-4">
            <section class="admin-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand-orange">Your plan</p>
                        <h2 class="mt-2 text-lg font-semibold text-brand-navy">Enabled modules</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ enabledCount }} on
                            <span v-if="lockedCount"> · {{ lockedCount }} locked</span>
                        </p>
                    </div>
                    <Link :href="route('settings.subscription')" class="text-sm font-medium text-brand-orange">
                        View your plan
                    </Link>
                </div>

                <p v-if="rows.length === 0" class="mt-6 text-sm text-gray-600">
                    No modules are listed for this shop.
                </p>

                <div v-else class="mt-5 grid gap-3 sm:grid-cols-2">
                    <article
                        v-for="mod in rows"
                        :key="mod.code"
                        class="rounded-lg border border-gray-100 px-4 py-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-sm font-medium text-brand-navy">{{ mod.name }}</h3>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ mod.code }}<span v-if="mod.is_core"> · core</span>
                                </p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium"
                                :class="mod.enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                            >
                                {{ mod.enabled ? 'On' : 'Locked' }}
                            </span>
                        </div>
                        <p v-if="mod.description" class="mt-2 text-xs text-gray-500">{{ mod.description }}</p>
                        <Link
                            v-if="upgradeHref(mod)"
                            :href="upgradeHref(mod)"
                            class="mt-3 inline-flex text-xs font-medium text-brand-orange"
                        >
                            View upgrade
                        </Link>
                    </article>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
