<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import ToggleSwitch from '@/Components/Admin/ToggleSwitch.vue';
import { formatMoney } from '@/utils/formatMoney';
import { Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    modules: { type: Array, default: () => [] },
    isNew: { type: Boolean, default: false },
    lockedDefault: { type: Boolean, default: false },
    submitLabel: { type: String, required: true },
});

const emit = defineEmits(['submit']);

const codeTouched = ref(!props.isNew);

watch(
    () => props.form.name,
    (name) => {
        if (!codeTouched.value) {
            props.form.code = name.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 40);
        }
    },
);

const coreModules = computed(() => props.modules.filter((mod) => mod.is_core));
const optionalModules = computed(() => props.modules.filter((mod) => !mod.is_core));
const chosenCount = computed(() => coreModules.value.length + optionalModules.value.filter((mod) => props.form.module_codes.includes(mod.code)).length);

const toggleModule = (code) => {
    const index = props.form.module_codes.indexOf(code);
    if (index >= 0) {
        props.form.module_codes.splice(index, 1);
    } else {
        props.form.module_codes.push(code);
    }
};

const selectAll = () => {
    props.form.module_codes = [...new Set([...props.form.module_codes, ...optionalModules.value.map((mod) => mod.code)])];
};

const selectNone = () => {
    const optional = new Set(optionalModules.value.map((mod) => mod.code));
    props.form.module_codes = props.form.module_codes.filter((code) => !optional.has(code));
};

const moduleError = computed(() => Object.entries(props.form.errors).find(([key]) => key.startsWith('module_codes'))?.[1]);

const pricePreview = computed(() => {
    const amount = Number(props.form.price || 0);
    return amount > 0 ? formatMoney(amount, 'BDT', { decimals: amount % 1 ? 2 : 0 }) : 'Free';
});
</script>

<template>
    <form class="grid gap-6 lg:grid-cols-[1fr_20rem]" @submit.prevent="emit('submit')">
        <div class="space-y-6">
            <section class="admin-card space-y-5">
                <div>
                    <h2 class="text-base font-semibold text-brand-navy">Plan details</h2>
                    <p class="text-xs text-gray-500">What shop owners see when they compare plans.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="plan-name" value="Plan name" />
                        <TextInput id="plan-name" v-model="form.name" class="mt-1 block w-full" placeholder="Growth" required />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="plan-code" value="Code" />
                        <TextInput
                            id="plan-code"
                            v-model="form.code"
                            class="mt-1 block w-full font-mono text-sm"
                            :class="!isNew ? 'bg-gray-50 text-gray-500' : ''"
                            :disabled="!isNew"
                            placeholder="growth"
                            @input="codeTouched = true"
                        />
                        <p v-if="!isNew" class="mt-1 text-xs text-gray-400">Codes are permanent so billing records stay linked.</p>
                        <InputError class="mt-1" :message="form.errors.code" />
                    </div>
                </div>

                <div>
                    <InputLabel for="plan-description" value="Short description" />
                    <textarea
                        id="plan-description"
                        v-model="form.description"
                        rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                        placeholder="For growing shops that need purchasing, CRM and accounting."
                    />
                    <InputError class="mt-1" :message="form.errors.description" />
                </div>

                <div class="sm:w-64">
                    <InputLabel for="plan-price" value="Monthly price" />
                    <div class="relative mt-1">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-semibold text-gray-400">৳</span>
                        <TextInput id="plan-price" v-model="form.price" type="number" min="0" step="0.01" class="block w-full pl-8" placeholder="0" required />
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Set 0 for a free plan.</p>
                    <InputError class="mt-1" :message="form.errors.price_monthly" />
                </div>
            </section>

            <section class="admin-card space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-brand-navy">Included modules</h2>
                        <p class="text-xs text-gray-500">Shops on this plan can open these modules. Anything else shows an upgrade screen.</p>
                    </div>
                    <div class="flex gap-1 text-xs font-semibold">
                        <button type="button" class="rounded-lg px-2.5 py-1.5 text-brand-navy ring-1 ring-gray-200 transition hover:bg-gray-50" @click="selectAll">Select all</button>
                        <button type="button" class="rounded-lg px-2.5 py-1.5 text-gray-500 ring-1 ring-gray-200 transition hover:bg-gray-50" @click="selectNone">Clear</button>
                    </div>
                </div>

                <InputError :message="moduleError" />

                <div class="grid gap-2 sm:grid-cols-2">
                    <button
                        v-for="mod in optionalModules"
                        :key="mod.code"
                        type="button"
                        class="flex items-start gap-3 rounded-xl border px-3.5 py-3 text-left transition"
                        :class="form.module_codes.includes(mod.code) ? 'border-brand-teal bg-brand-teal/5 ring-1 ring-brand-teal/40' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                        :aria-pressed="form.module_codes.includes(mod.code)"
                        @click="toggleModule(mod.code)"
                    >
                        <span
                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition"
                            :class="form.module_codes.includes(mod.code) ? 'border-brand-teal bg-brand-teal text-white' : 'border-gray-300 bg-white'"
                        >
                            <svg v-if="form.module_codes.includes(mod.code)" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-brand-navy">{{ mod.name }}</span>
                            <span v-if="mod.description" class="mt-0.5 line-clamp-2 block text-xs text-gray-500">{{ mod.description }}</span>
                        </span>
                    </button>
                </div>

                <div v-if="coreModules.length" class="rounded-xl bg-gray-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Always included</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <span v-for="mod in coreModules" :key="mod.code" class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-200">{{ mod.name }}</span>
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
            <section class="overflow-hidden rounded-2xl bg-brand-navy text-white shadow-sm">
                <div class="p-5">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-white/50">Preview</p>
                        <span v-if="form.is_default" class="rounded-full bg-brand-orange px-2 py-0.5 text-[11px] font-semibold">Default</span>
                    </div>
                    <p class="mt-3 text-xl font-semibold">{{ form.name || 'Plan name' }}</p>
                    <p class="mt-1 line-clamp-3 text-sm text-white/60">{{ form.description || 'A short description helps owners pick the right plan.' }}</p>
                    <p class="mt-4 text-3xl font-semibold">{{ pricePreview }}<span v-if="Number(form.price) > 0" class="text-sm font-normal text-white/60"> / month</span></p>
                </div>
                <div class="border-t border-white/10 bg-white/5 px-5 py-3 text-sm text-white/80">
                    {{ chosenCount }} of {{ modules.length }} modules
                </div>
            </section>

            <section class="admin-card space-y-4">
                <ToggleSwitch
                    v-model="form.is_active"
                    label="Offered to shops"
                    :description="lockedDefault ? 'The default plan is always on offer.' : 'Hidden plans keep their current shops but can\'t be picked for new ones.'"
                    :class="lockedDefault ? 'pointer-events-none opacity-60' : ''"
                />
                <InputError :message="form.errors.is_active" />
                <div class="border-t border-gray-100 pt-4">
                    <ToggleSwitch
                        v-model="form.is_default"
                        label="Default for new shops"
                        :description="lockedDefault ? 'To change this, make another plan the default.' : 'New shops start on this plan unless you pick another.'"
                        :class="lockedDefault ? 'pointer-events-none opacity-60' : ''"
                    />
                </div>
            </section>

            <div class="flex gap-2">
                <button
                    type="submit"
                    class="flex-1 rounded-xl bg-brand-orange px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-orange-dark disabled:opacity-60"
                    :disabled="form.processing"
                >
                    {{ submitLabel }}
                </button>
                <Link :href="route('platform.plans.index')" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-600 ring-1 ring-gray-200 transition hover:bg-gray-50">
                    Cancel
                </Link>
            </div>
        </aside>
    </form>
</template>
