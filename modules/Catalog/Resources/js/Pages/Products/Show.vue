<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

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
    draft: 'bg-gray-100 text-gray-600',
    pending_review: 'bg-amber-50 text-amber-700',
    approved: 'bg-sky-50 text-sky-700',
    active: 'bg-emerald-50 text-emerald-700',
    archived: 'bg-orange-50 text-brand-orange',
};

const confirmDelete = () => {
    deleteForm.delete(route('products.destroy', props.product.id));
};

const formatDate = formatDateTime;
</script>

<template>
    <Head :title="product.name" />

    <AdminLayout :title="product.name">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                    :class="statusMeta[product.status]"
                >
                    {{ product.status }}
                </span>
                <span class="text-xs text-gray-400">{{ product.type }} product</span>
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
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2 text-sm">
                        <div>
                            <dt class="text-gray-500">SKU</dt>
                            <dd class="font-medium text-brand-navy">{{ product.sku || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Internal code</dt>
                            <dd class="font-medium text-brand-navy">{{ product.internal_code || '—' }}</dd>
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
                            <dt class="text-gray-500">Unit</dt>
                            <dd class="font-medium text-brand-navy">
                                {{ product.unit ? `${product.unit.name} (${product.unit.code})` : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Publication</dt>
                            <dd class="font-medium text-brand-navy">{{ product.publication_status }}</dd>
                        </div>
                    </dl>
                    <p v-if="product.description" class="mt-4 text-sm text-gray-600">{{ product.description }}</p>
                </section>

                <section v-if="product.variants.length" class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Variants ({{ product.variants.length }})</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500">
                                    <th class="pb-2 pr-4">SKU</th>
                                    <th class="pb-2 pr-4">Name</th>
                                    <th class="pb-2">Attributes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="variant in product.variants" :key="variant.id" class="border-t border-gray-100">
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

                <section v-if="product.informational_attributes.length" class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Specifications</h2>
                    <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
                        <div v-for="attr in product.informational_attributes" :key="attr.attribute_id">
                            <dt class="text-gray-500">{{ attr.attribute_name }}</dt>
                            <dd class="font-medium text-brand-navy">{{ attr.option_value || attr.value || '—' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="admin-card">
                    <h2 class="text-sm font-semibold text-brand-navy">Media</h2>
                    <div v-if="product.media.length" class="mt-3 grid grid-cols-2 gap-2">
                        <img
                            v-for="media in product.media"
                            :key="media.id"
                            :src="media.url"
                            alt=""
                            class="aspect-square rounded-lg object-cover ring-1 ring-gray-200"
                            :class="media.is_primary ? 'ring-2 ring-brand-teal' : ''"
                        />
                    </div>
                    <p v-else class="mt-2 text-sm text-gray-500">No images</p>
                </section>

                <section class="admin-card text-sm text-gray-500 space-y-1">
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
