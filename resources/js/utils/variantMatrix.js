/**
 * Build and merge product variant matrices from attribute options.
 */

/**
 * @param {Array<{ attribute_id: number, attribute_name?: string, options: Array<{ id: number, value: string }> }>} groups
 * @returns {Array<Array<{ attribute_id: number, attribute_option_id: number, attribute_name: string, option_value: string }>>}
 */
export function cartesianCombinations(groups) {
    const usable = (groups || []).filter((g) => g?.attribute_id && Array.isArray(g.options) && g.options.length > 0);

    if (!usable.length) {
        return [];
    }

    return usable.reduce((acc, group) => {
        const next = [];
        const options = group.options;

        for (const prefix of acc.length ? acc : [[]]) {
            for (const option of options) {
                next.push([
                    ...prefix,
                    {
                        attribute_id: group.attribute_id,
                        attribute_name: group.attribute_name || '',
                        attribute_option_id: option.id,
                        option_value: option.value,
                    },
                ]);
            }
        }

        return next;
    }, []);
}

/**
 * Stable key for a combination of attribute options.
 * @param {Array<{ attribute_id: number|string, attribute_option_id: number|string }>} attributes
 */
export function combinationKey(attributes) {
    return (attributes || [])
        .filter((a) => a?.attribute_id && a?.attribute_option_id)
        .map((a) => `${a.attribute_id}:${a.attribute_option_id}`)
        .sort()
        .join('|');
}

/**
 * Human label: "Red / M"
 */
export function combinationLabel(attributes) {
    return (attributes || [])
        .map((a) => a.option_value || a.value || '')
        .filter(Boolean)
        .join(' / ');
}

/**
 * SKU-safe fragment from option values.
 */
export function combinationSkuSuffix(attributes) {
    return (attributes || [])
        .map((a) => String(a.option_value || '')
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .toUpperCase()
            .replace(/[^A-Z0-9]+/g, '')
            .slice(0, 8))
        .filter(Boolean)
        .join('-');
}

/**
 * @param {object} params
 * @param {Array} params.combinations
 * @param {Array} [params.existing]
 * @param {string} params.productName
 * @param {string} [params.skuPrefix]
 * @param {(name: string, prefix?: string) => string} params.skuFromName
 */
export function mergeGeneratedVariants({
    combinations,
    existing = [],
    productName,
    skuPrefix = '',
    skuFromName,
}) {
    const existingByKey = new Map();
    for (const row of existing) {
        const key = combinationKey(row.attributes || []);
        if (key) {
            existingByKey.set(key, row);
        }
    }

    const base = skuFromName(productName || 'VARIANT', skuPrefix);

    return combinations.map((combo, index) => {
        const key = combinationKey(combo);
        const prev = existingByKey.get(key);
        const label = combinationLabel(combo);
        const suffix = combinationSkuSuffix(combo) || `V${String(index + 1).padStart(2, '0')}`;
        const sku = prev?.sku || `${base}-${suffix}`;

        if (prev) {
            return {
                ...prev,
                name: prev.name || label,
                attributes: combo.map((c) => ({
                    attribute_id: c.attribute_id,
                    attribute_option_id: c.attribute_option_id,
                })),
                _comboLabel: label,
            };
        }

        return {
            sku,
            barcode: sku,
            name: label,
            weight: '',
            is_active: true,
            media_library_ids: [],
            media_previews: [],
            existing_media: [],
            attributes: combo.map((c) => ({
                attribute_id: c.attribute_id,
                attribute_option_id: c.attribute_option_id,
            })),
            _comboLabel: label,
            _skuLocked: true,
            _barcodeLocked: true,
        };
    });
}

export const VARIANT_MATRIX_SOFT_LIMIT = 48;
export const VARIANT_MATRIX_HARD_LIMIT = 100;
