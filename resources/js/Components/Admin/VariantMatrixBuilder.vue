<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import {
    VARIANT_MATRIX_HARD_LIMIT,
    VARIANT_MATRIX_SOFT_LIMIT,
    cartesianCombinations,
    combinationKey,
    mergeGeneratedVariants,
} from '@/utils/variantMatrix';
import { skuFromName } from '@/utils/productIdentifiers';
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    variantAttributes: { type: Array, default: () => [] },
    modelValue: { type: Array, default: () => [] },
    productName: { type: String, default: '' },
    skuPrefix: { type: String, default: '' },
    /** When true, seed picker from existing variant rows once */
    seedFromVariants: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'open-media', 'remove-media']);

const matrix = reactive({
    selectedAttributeIds: [],
    selectedOptions: {},
    seeded: false,
});

const selectedAttributes = computed(() =>
    props.variantAttributes.filter((a) => matrix.selectedAttributeIds.includes(a.id)),
);

const optionGroups = computed(() =>
    selectedAttributes.value.map((attr) => ({
        attribute_id: attr.id,
        attribute_name: attr.name,
        options: (attr.options || []).filter((o) => (matrix.selectedOptions[attr.id] || []).includes(o.id)),
    })),
);

const previewCount = computed(() => {
    const sizes = optionGroups.value.map((g) => g.options.length).filter((n) => n > 0);
    if (!sizes.length) return 0;
    return sizes.reduce((a, b) => a * b, 1);
});

const previewOverSoft = computed(() => previewCount.value > VARIANT_MATRIX_SOFT_LIMIT);
const previewOverHard = computed(() => previewCount.value > VARIANT_MATRIX_HARD_LIMIT);
const canGenerate = computed(
    () =>
        selectedAttributes.value.length > 0 &&
        optionGroups.value.every((g) => g.options.length > 0) &&
        previewCount.value > 0 &&
        !previewOverHard.value,
);

const variants = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

watch(
    () => [props.seedFromVariants, props.modelValue],
    () => {
        if (!props.seedFromVariants || matrix.seeded) return;
        if (!props.modelValue?.length) return;

        const attrIds = [];
        const opts = {};

        for (const row of props.modelValue) {
            for (const attr of row.attributes || []) {
                if (!attr.attribute_id || !attr.attribute_option_id) continue;
                if (!attrIds.includes(attr.attribute_id)) attrIds.push(attr.attribute_id);
                if (!opts[attr.attribute_id]) opts[attr.attribute_id] = [];
                if (!opts[attr.attribute_id].includes(attr.attribute_option_id)) {
                    opts[attr.attribute_id].push(attr.attribute_option_id);
                }
            }
        }

        if (attrIds.length) {
            matrix.selectedAttributeIds = attrIds;
            matrix.selectedOptions = opts;
            matrix.seeded = true;
        }
    },
    { immediate: true, deep: true },
);

const toggleAttribute = (attrId) => {
    const index = matrix.selectedAttributeIds.indexOf(attrId);
    if (index === -1) {
        matrix.selectedAttributeIds.push(attrId);
        if (!matrix.selectedOptions[attrId]) {
            matrix.selectedOptions[attrId] = [];
        }
        return;
    }

    matrix.selectedAttributeIds.splice(index, 1);
    delete matrix.selectedOptions[attrId];
};

const toggleOption = (attrId, optionId) => {
    if (!matrix.selectedAttributeIds.includes(attrId)) {
        matrix.selectedAttributeIds.push(attrId);
    }
    const list = matrix.selectedOptions[attrId] ? [...matrix.selectedOptions[attrId]] : [];
    const index = list.indexOf(optionId);
    if (index === -1) list.push(optionId);
    else list.splice(index, 1);
    matrix.selectedOptions[attrId] = list;
};

const selectAllOptions = (attr) => {
    if (!matrix.selectedAttributeIds.includes(attr.id)) {
        matrix.selectedAttributeIds.push(attr.id);
    }
    matrix.selectedOptions[attr.id] = (attr.options || []).map((o) => o.id);
};

const clearOptions = (attrId) => {
    matrix.selectedOptions[attrId] = [];
};

const generate = () => {
    if (!canGenerate.value) return;

    const combinations = cartesianCombinations(optionGroups.value);
    const next = mergeGeneratedVariants({
        combinations,
        existing: variants.value,
        productName: props.productName,
        skuPrefix: props.skuPrefix,
        skuFromName,
    });

    const keptKeys = new Set(next.map((row) => combinationKey(row.attributes)));
    const removed = variants.value.filter((row) => {
        const key = combinationKey(row.attributes || []);
        return key && row.id && !keptKeys.has(key);
    });

    if (removed.length) {
        const ok = window.confirm(
            `${removed.length} existing variant(s) are not in the new matrix and will be removed on save. Continue?`,
        );
        if (!ok) return;
    }

    variants.value = next;
};

const removeVariant = (index) => {
    variants.value = variants.value.filter((_, i) => i !== index);
};

const comboLabel = (variant) =>
    variant._comboLabel ||
    (variant.attributes || [])
        .map((a) => {
            const attr = props.variantAttributes.find((x) => x.id === a.attribute_id);
            const opt = attr?.options?.find((o) => o.id === a.attribute_option_id);
            return opt?.value || '';
        })
        .filter(Boolean)
        .join(' / ') ||
    variant.name ||
    variant.sku;

const thumb = (variant) => variant.media_previews?.[0] || variant.existing_media?.[0] || null;
</script>

<template>
    <div class="space-y-4">
        <div v-if="!variantAttributes.length" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            No variant attributes yet. Create Color / Size (type: Variant) under Catalog → Attributes, then come back.
        </div>

        <template v-else>
            <!-- Step 1: attributes -->
            <div>
                <div class="mb-2 flex items-end justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-brand-navy">1. Choose attributes</p>
                        <p class="text-[11px] text-gray-400">Which options define this product’s SKUs?</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="attr in variantAttributes"
                        :key="attr.id"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-xs font-medium transition ring-1"
                        :class="
                            matrix.selectedAttributeIds.includes(attr.id)
                                ? 'bg-brand-navy text-white ring-brand-navy'
                                : 'bg-white text-brand-navy ring-gray-200 hover:ring-brand-navy/30'
                        "
                        @click="toggleAttribute(attr.id)"
                    >
                        {{ attr.name }}
                    </button>
                </div>
            </div>

            <!-- Step 2: options -->
            <div v-if="selectedAttributes.length" class="space-y-3">
                <div>
                    <p class="text-sm font-semibold text-brand-navy">2. Pick values</p>
                    <p class="text-[11px] text-gray-400">Only selected values are combined into variants</p>
                </div>
                <div
                    v-for="attr in selectedAttributes"
                    :key="attr.id"
                    class="rounded-xl border border-gray-200 bg-gray-50/50 p-3"
                >
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ attr.name }}</p>
                        <div class="flex gap-2 text-[11px]">
                            <button type="button" class="font-medium text-brand-teal hover:underline" @click="selectAllOptions(attr)">
                                All
                            </button>
                            <button type="button" class="text-gray-400 hover:text-brand-navy" @click="clearOptions(attr.id)">
                                Clear
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="opt in attr.options || []"
                            :key="opt.id"
                            type="button"
                            class="rounded-lg px-2.5 py-1 text-xs transition ring-1"
                            :class="
                                (matrix.selectedOptions[attr.id] || []).includes(opt.id)
                                    ? 'bg-brand-teal/15 text-brand-navy ring-brand-teal/40'
                                    : 'bg-white text-gray-600 ring-gray-200 hover:ring-gray-300'
                            "
                            @click="toggleOption(attr.id, opt.id)"
                        >
                            {{ opt.value }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3: generate -->
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-dashed border-gray-200 bg-white px-4 py-3"
            >
                <div>
                    <p class="text-sm font-medium text-brand-navy">
                        <template v-if="previewCount">
                            {{ previewCount }} combination{{ previewCount === 1 ? '' : 's' }}
                        </template>
                        <template v-else>Select attributes & values</template>
                    </p>
                    <p v-if="previewOverHard" class="text-[11px] text-red-600">
                        Max {{ VARIANT_MATRIX_HARD_LIMIT }} variants — remove some options
                    </p>
                    <p v-else-if="previewOverSoft" class="text-[11px] text-amber-600">
                        Large matrix ({{ previewCount }}). Consider fewer options.
                    </p>
                    <p v-else class="text-[11px] text-gray-400">
                        Example: Color × Size → Red/M, Red/L, Blue/M…
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg bg-brand-navy px-3.5 py-2 text-sm font-medium text-white transition hover:bg-brand-navy/90 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="!canGenerate"
                    @click="generate"
                >
                    {{ variants.length ? 'Regenerate variants' : 'Generate variants' }}
                </button>
            </div>
        </template>

        <!-- Generated rows -->
        <div v-if="variants.length" class="space-y-3">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-semibold text-brand-navy">
                    Variants
                    <span class="ml-1 font-normal text-gray-400">({{ variants.length }})</span>
                </p>
                <p class="text-[11px] text-gray-400">One image per variant · edit SKU if needed</p>
            </div>

            <article
                v-for="(variant, index) in variants"
                :key="combinationKey(variant.attributes) || variant.id || index"
                class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50/40"
            >
                <div class="flex items-center justify-between gap-2 border-b border-gray-200/80 bg-white px-3 py-2">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-brand-navy/5 text-[11px] font-semibold text-brand-navy">
                            {{ String(index + 1).padStart(2, '0') }}
                        </span>
                        <p class="truncate text-sm font-medium text-brand-navy">{{ comboLabel(variant) }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-1.5 text-[11px] text-gray-500">
                            <input v-model="variant.is_active" type="checkbox" class="rounded border-gray-300 text-brand-teal focus:ring-brand-teal" />
                            Active
                        </label>
                        <button type="button" class="text-xs text-gray-400 hover:text-red-600" @click="removeVariant(index)">
                            Remove
                        </button>
                    </div>
                </div>

                <div class="grid gap-4 p-3 sm:grid-cols-[88px_1fr]">
                    <div>
                        <button
                            v-if="!thumb(variant)"
                            type="button"
                            class="flex h-[88px] w-[88px] flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-gray-300 bg-white text-[10px] text-gray-400 transition hover:border-brand-teal hover:text-brand-navy"
                            @click="emit('open-media', index)"
                        >
                            Image
                        </button>
                        <div
                            v-else
                            class="group relative h-[88px] w-[88px] overflow-hidden rounded-xl ring-1 ring-gray-200"
                        >
                            <img :src="thumb(variant).url" alt="" class="h-full w-full object-cover" />
                            <div class="absolute inset-0 flex flex-col justify-between bg-gradient-to-b from-black/40 via-transparent to-black/50 opacity-0 transition group-hover:opacity-100">
                                <button type="button" class="px-1 py-1 text-[9px] text-white" @click="emit('open-media', index)">
                                    Change
                                </button>
                                <button type="button" class="px-1 py-1 text-[9px] text-white" @click="emit('remove-media', index)">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid min-w-0 gap-2 sm:grid-cols-2">
                        <div>
                            <InputLabel value="SKU" />
                            <TextInput v-model="variant.sku" class="mt-1 block w-full font-mono text-xs" />
                        </div>
                        <div>
                            <InputLabel value="Barcode" />
                            <TextInput v-model="variant.barcode" class="mt-1 block w-full font-mono text-xs" />
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>
