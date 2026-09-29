import { computed, unref } from 'vue';

/**
 * Mirrors DeliveryRateService::quote() so the customer sees the same total the server will charge.
 *
 * @param {import('vue').Ref<object>} cartRef shared `shopCart` prop
 * @param {import('vue').Ref<string|null>} zoneCodeRef selected delivery area, null before choosing
 */
export function useCheckoutTotals(cartRef, zoneCodeRef = null) {
    const cart = computed(() => unref(cartRef) ?? {});
    const delivery = computed(() => cart.value.delivery ?? { enabled: false, zones: [], free_threshold: null });

    const subtotal = computed(() => Number(cart.value.subtotal || 0));
    const discount = computed(() => Number(cart.value.discount || 0));
    const afterDiscount = computed(() => Math.max(0, subtotal.value - discount.value));

    const freeThreshold = computed(() => (delivery.value.free_threshold ? Number(delivery.value.free_threshold) : null));
    const qualifiesForFree = computed(() => freeThreshold.value !== null && afterDiscount.value >= freeThreshold.value);
    const freeRemaining = computed(() => (freeThreshold.value === null ? null : Math.max(0, freeThreshold.value - afterDiscount.value)));
    const freeProgress = computed(() =>
        freeThreshold.value === null ? 0 : Math.min(100, (afterDiscount.value / Math.max(freeThreshold.value, 1)) * 100),
    );

    const selectedZone = computed(() => delivery.value.zones.find((zone) => zone.code === unref(zoneCodeRef)) ?? null);
    const cheapestRate = computed(() => (delivery.value.zones.length ? Math.min(...delivery.value.zones.map((zone) => Number(zone.rate))) : 0));

    /** null means "not known yet" (delivery is charged but no area is chosen). */
    const shippingFee = computed(() => {
        if (!delivery.value.enabled || qualifiesForFree.value) {
            return 0;
        }
        return selectedZone.value ? Number(selectedZone.value.rate) : null;
    });

    const total = computed(() => afterDiscount.value + (shippingFee.value ?? 0));

    const zoneFee = (zone) => (qualifiesForFree.value ? 0 : Number(zone.rate));

    return {
        delivery,
        subtotal,
        discount,
        freeThreshold,
        qualifiesForFree,
        freeRemaining,
        freeProgress,
        selectedZone,
        cheapestRate,
        shippingFee,
        total,
        zoneFee,
    };
}
