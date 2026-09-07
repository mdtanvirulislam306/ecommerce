<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const deleteForm = useForm({});
const actionForm = useForm({});
const showDeleteModal = ref(false);

const actionRoutes = {
    submit_review: 'products.submit-review',
    approve: 'products.approve',
    reject: 'products.reject',
    activate: 'products.activate',
    archive: 'products.archive',
};

const runAction = (action) => {
    const routeName = actionRoutes[action];
    if (!routeName) return;

    actionForm.post(route(routeName, props.product.id), { preserveScroll: true });
};

const statusMeta = {
    draft: 'bg-gray-100 text-gray-600 ring-1 ring-gray-200',
    pending_review: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
    approved: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
    active: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
    archived: 'bg-orange-50 text-brand-orange ring-1 ring-orange-200',
};

const productMedia = computed(() => (props.product.media || []).filter((m) => !m.product_variant_id));
const variants = computed(() => props.product.variants || []);
const specs = computed(() => props.product.informational_attributes || []);
const collections = computed(() => props.product.collections || []);

const confirmDelete = () => {
    deleteForm.delete(route('products.destroy', props.product.id));
};

const formatDate = formatDateTime;
</script>

<template>
    <Head :title="product.name" />

    <AdminLayout :title="product.name">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="statusMeta[product.status]"
                >
                    {{ product.status_label || product.status }}
                </span>
                <span class="text-xs text-gray-400 capitalize">{{ product.type_label || product.type }} product</span>
                <span v-if="product.publication_status_label" class="text-xs text-gray-400">
                    · {{ product.publication_status_label }}
                </span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <template v-if="product.approval_actions?.length">
                    <button
                        v-for="item in product.approval_actions"
                        :key="item.action"
                        type="button"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-brand-navy hover:border-brand-teal hover:text-brand-orange"
                        :disabled="actionForm.processing"
                        @click="runAction(item.action)"
                    >
                        {{ item.label }}
                    </button>
                </template>
                <Link
                    :href="route('products.edit', product.id)"
                    class="rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                >
                    Edit
                </Link>
                <Link :href="route('products.index')" class="text-sm font-medium text-brand-navy hover:text-brand-orange">
                    ← Back
                </Link>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
            <div class="space-y-5">
                <section class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Overview</h2>
                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-gray-500">SKU</dt>
                            <dd class="font-medium text-brand-navy">{{ product.sku || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Barcode</dt>
                            <dd class="font-medium text-brand-navy">{{ product.barcode || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Internal code</dt>
                            <dd class="font-medium text-brand-navy">{{ product.internal_code || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Slug</dt>
                            <dd class="font-mono text-xs font-medium text-brand-navy">{{ product.slug || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Brand</dt>
                            <dd class="font-medium text-brand-navy">{{ product.brand?.name || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Primary category</dt>
                            <dd class="font-medium text-brand-navy">{{ product.primary_category?.name || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Family</dt>
                            <dd class="font-medium text-brand-navy">{{ product.product_family?.name || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Unit</dt>
                            <dd class="font-medium text-brand-navy">
                                {{ product.unit ? `${product.unit.name} (${product.unit.code})` : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Selling price</dt>
                            <dd class="font-medium text-brand-navy">
                                {{ product.selling_price != null ? product.selling_price : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Current stock</dt>
                            <dd class="font-medium text-brand-navy">
                                {{ product.current_stock != null ? product.current_stock : '—' }}
                            </dd>
                        </div>
                    </dl>
                    <p v-if="collections.length" class="mt-4 text-sm text-gray-600">
                        <span class="text-gray-500">Collections:</span>
                        {{ collections.map((c) => c.name).join(', ') }}
                    </p>
                    <p v-if="product.description" class="mt-4 text-sm text-gray-600">{{ product.description }}</p>
                </section>

                <section v-if="variants.length" class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Variants ({{ variants.length }})</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500">
                                    <th class="pb-2 pr-4">Image</th>
                                    <th class="pb-2 pr-4">SKU</th>
                                    <th class="pb-2 pr-4">Name</th>
                                    <th class="pb-2">Attributes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="variant in variants" :key="variant.id" class="border-t border-gray-100">
                                    <td class="py-2 pr-4">
                                        <img
                                            v-if="variant.image_url"
                                            :src="variant.image_url"
                                            alt=""
                                            class="h-10 w-10 rounded-lg object-cover ring-1 ring-gray-200"
                                        />
                                        <span v-else class="text-xs text-gray-400">—</span>
                                    </td>
                                    <td class="py-2 pr-4 font-medium text-brand-navy">{{ variant.sku }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ variant.name || '—' }}</td>
                                    <td class="py-2 text-gray-600">
                                        <span
                                            v-for="attr in variant.attributes"
                                            :key="`${variant.id}-${attr.attribute_id}`"
                                            class="mr-2 inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs"
                                        >
                                            {{ attr.option_value }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section v-if="specs.length" class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Specifications</h2>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div v-for="attr in specs" :key="attr.attribute_id">
                            <dt class="text-gray-500">{{ attr.attribute_name }}</dt>
                            <dd class="font-medium text-brand-navy">{{ attr.option_value || attr.value || '—' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Media</h2>
                    <div v-if="productMedia.length" class="mt-3 grid grid-cols-2 gap-2">
                        <img
                            v-for="media in productMedia"
                            :key="media.id"
                            :src="media.url"
                            alt=""
                            class="aspect-square rounded-lg object-cover ring-1 ring-gray-200"
                            :class="media.is_primary ? 'ring-2 ring-brand-teal' : ''"
                        />
                    </div>
                    <p v-else class="mt-2 text-sm text-gray-500">No images</p>
                </section>

                <section class="admin-card space-y-1 text-sm text-gray-500">
                    <p>Created {{ formatDate(product.created_at) }}</p>
                    <p>Updated {{ formatDate(product.updated_at) }}</p>
                    <button
                        type="button"
                        class="mt-3 text-sm text-red-600 hover:text-red-700"
                        @click="showDeleteModal = true"
                    >
                        Delete product
                    </button>
                </section>
            </aside>
        </div>

        <DeleteConfirmModal
            :show="showDeleteModal"
            title="Delete this product?"
            message="This product and its variants will be permanently removed."
            :item-name="product.name"
            confirm-label="Delete product"
            :processing="deleteForm.processing"
            @close="showDeleteModal = false"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
