import { reactive } from 'vue';

const state = reactive({
    cartOpen: false,
    checkoutOpen: false,
    product: null,
    variantProduct: null,
    loadingProduct: false,
    requesting: false,
});

const CART_TARGET_ID = 'shop-cart-target';

/** @type {{ current: Element|null }} */
const cartButtonEl = { current: null };

/**
 * @param {MouseEvent|Event|Element|null|undefined} fromEventOrEl
 * @returns {Element|null}
 */
const resolveFlyOrigin = (fromEventOrEl) => {
    if (!fromEventOrEl) {
        return null;
    }

    if (fromEventOrEl.nodeType === 1) {
        return fromEventOrEl;
    }

    const target = fromEventOrEl.currentTarget || fromEventOrEl.target;
    if (!target || target.nodeType !== 1) {
        return null;
    }

    const root =
        target.closest('[data-shop-fly-root]') ||
        target.closest('article') ||
        target.closest('aside') ||
        target;

    const img = root.querySelector?.('img');
    if (img && img.nodeType === 1) {
        return img;
    }

    return target;
};

const resolveCartTarget = () =>
    cartButtonEl.current ||
    document.getElementById(CART_TARGET_ID) ||
    document.querySelector('[data-shop-cart-target]') ||
    document.querySelector('button[aria-label="Open cart"]');

export function useShopUi() {
    const openCart = () => {
        state.cartOpen = true;
        state.checkoutOpen = false;
    };

    const closeCart = () => {
        state.cartOpen = false;
    };

    const openCheckout = () => {
        state.checkoutOpen = true;
        state.cartOpen = false;
        state.product = null;
        state.variantProduct = null;
    };

    const closeCheckout = () => {
        state.checkoutOpen = false;
    };

    const closeProduct = () => {
        state.product = null;
    };

    const closeVariant = () => {
        state.variantProduct = null;
    };

    const beginRequest = () => {
        state.requesting = true;
    };

    const endRequest = () => {
        state.requesting = false;
    };

    const setCartButtonEl = (el) => {
        cartButtonEl.current = el || null;
    };

    const fetchProduct = async (slug) => {
        state.loadingProduct = true;
        beginRequest();
        try {
            const { data } = await window.axios.get(route('shop.products.quick', slug));
            return data;
        } finally {
            state.loadingProduct = false;
            endRequest();
        }
    };

    const openProductModal = async (slugOrProduct) => {
        state.variantProduct = null;
        state.checkoutOpen = false;
        if (typeof slugOrProduct === 'string') {
            state.product = await fetchProduct(slugOrProduct);
            return;
        }
        if (slugOrProduct?.variants) {
            state.product = slugOrProduct;
            return;
        }
        state.product = await fetchProduct(slugOrProduct.slug);
    };

    const openVariantModal = async (slugOrProduct) => {
        state.product = null;
        state.checkoutOpen = false;
        if (typeof slugOrProduct === 'string') {
            state.variantProduct = await fetchProduct(slugOrProduct);
            return;
        }
        if (slugOrProduct?.variants) {
            state.variantProduct = slugOrProduct;
            return;
        }
        state.variantProduct = await fetchProduct(slugOrProduct.slug);
    };

    /**
     * Fly a product thumbnail into the floating cart button (Web Animations API).
     */
    const flyToCart = (imageUrl, fromEventOrEl) => {
        try {
            const fromEl = resolveFlyOrigin(fromEventOrEl);
            const toEl = resolveCartTarget();

            if (!toEl) {
                return Promise.resolve();
            }

            const from = fromEl?.getBoundingClientRect?.() ?? {
                left: fromEventOrEl?.clientX ?? window.innerWidth / 2,
                top: fromEventOrEl?.clientY ?? window.innerHeight / 2,
                width: 0,
                height: 0,
            };
            const to = toEl.getBoundingClientRect();

            const startX = from.width > 0
                ? from.left + from.width / 2
                : (fromEventOrEl?.clientX ?? from.left);
            const startY = from.height > 0
                ? from.top + from.height / 2
                : (fromEventOrEl?.clientY ?? from.top);
            const size = Math.max(48, Math.min(72, from.width || from.height || 56));
            const endX = to.left + to.width / 2;
            const endY = to.top + to.height / 2;

            const flyer = document.createElement('div');
            flyer.className = 'shop-fly-item';
            flyer.setAttribute('aria-hidden', 'true');
            flyer.style.cssText = [
                'position:fixed',
                'z-index:9999',
                `width:${size}px`,
                `height:${size}px`,
                `left:${startX - size / 2}px`,
                `top:${startY - size / 2}px`,
                'border-radius:12px',
                'overflow:hidden',
                'pointer-events:none',
                'box-shadow:0 12px 30px rgba(44,75,96,0.28)',
                'will-change:transform,opacity',
                imageUrl ? '' : 'background:#F27D42',
            ].filter(Boolean).join(';');

            if (imageUrl) {
                const img = document.createElement('img');
                img.src = imageUrl;
                img.alt = '';
                img.draggable = false;
                img.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block';
                flyer.appendChild(img);
            }

            document.body.appendChild(flyer);

            const dx = endX - startX;
            const dy = endY - startY;
            // Arc control point: rise above the path, slight outward lean
            const cpX = startX + dx * 0.4 + (dx >= 0 ? 1 : -1) * Math.min(140, Math.abs(dx) * 0.35 + 40);
            const cpY = Math.min(startY, endY) - Math.max(90, Math.abs(dy) * 0.4 + 70);

            const finish = () => {
                flyer.remove();
                toEl.classList.add('shop-cart-pop');
                window.setTimeout(() => toEl.classList.remove('shop-cart-pop'), 350);
            };

            const pointOnCurve = (t) => {
                const u = 1 - t;
                const x = u * u * startX + 2 * u * t * cpX + t * t * endX;
                const y = u * u * startY + 2 * u * t * cpY + t * t * endY;
                return { x, y };
            };

            if (typeof flyer.animate === 'function') {
                const steps = 16;
                const keyframes = [];
                for (let i = 0; i <= steps; i += 1) {
                    const t = i / steps;
                    const { x, y } = pointOnCurve(t);
                    const scale = 1 - t * 0.82;
                    const opacity = Math.max(0.12, 1 - t * 0.9);
                    keyframes.push({
                        transform: `translate(${x - startX}px, ${y - startY}px) scale(${scale}) rotate(${t * (dx >= 0 ? 18 : -18)}deg)`,
                        opacity,
                        offset: t,
                    });
                }

                return flyer
                    .animate(keyframes, {
                        duration: 820,
                        easing: 'cubic-bezier(0.22, 0.61, 0.36, 1)',
                        fill: 'forwards',
                    })
                    .finished.then(finish)
                    .catch(finish);
            }

            // Fallback: step along the curve with rAF
            const duration = 820;
            const started = performance.now();
            return new Promise((resolve) => {
                const tick = (now) => {
                    const t = Math.min(1, (now - started) / duration);
                    const eased = 1 - (1 - t) ** 2;
                    const { x, y } = pointOnCurve(eased);
                    const scale = 1 - eased * 0.82;
                    flyer.style.transform = `translate(${x - startX}px, ${y - startY}px) scale(${scale})`;
                    flyer.style.opacity = String(Math.max(0.12, 1 - eased * 0.9));
                    if (t < 1) {
                        requestAnimationFrame(tick);
                        return;
                    }
                    finish();
                    resolve();
                };
                requestAnimationFrame(tick);
            });
        } catch {
            return Promise.resolve();
        }
    };

    return {
        state,
        CART_TARGET_ID,
        openCart,
        closeCart,
        openCheckout,
        closeCheckout,
        closeProduct,
        closeVariant,
        beginRequest,
        endRequest,
        setCartButtonEl,
        fetchProduct,
        openProductModal,
        openVariantModal,
        flyToCart,
    };
}
