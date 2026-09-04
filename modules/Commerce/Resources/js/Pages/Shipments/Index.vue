<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    shipments: { type: Object, required: true },
    couriers: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ search: '', per_page: 25 }) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const showFormModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);

const emptyForm = () => ({
    courier_id: '',
    tracking_number: '',
    status: 'pending',
    recipient_name: '',
    destination: '',
    shipped_at: '',
    notes: '',
});

const form = useForm(emptyForm());
const deleteForm = useForm({});
const meta = computed(() => paginationMeta(props.shipments));

const openCreate = () => {
    editing.value = null;
    form.defaults(emptyForm());
    form.reset();
    showFormModal.value = true;
};

const openEdit = (item) => {
    editing.value = item;
    form.defaults({
        courier_id: item.courier_id || '',
        tracking_number: item.tracking_number,
        status: item.status,
        recipient_name: item.recipient_name,
        destination: item.destination || '',
        shipped_at: item.shipped_at ? item.shipped_at.slice(0, 16) : '',
        notes: item.notes || '',
    });
    form.reset();
    showFormModal.value = true;
};

const closeFormModal = () => {
    if (!form.processing) {
        showFormModal.value = false;
        editing.value = null;
    }
};

const submit = () => {
    if (editing.value) {
        form.put(route('commerce.shipping.shipments.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
                editing.value = null;
            },
        });
    } else {
        form.post(route('commerce.shipping.shipments.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
            },
        });
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleteForm.delete(route('commerce.shipping.shipments.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

let searchTimer = null;
const visitIndex = () => {
    router.get(
        route('commerce.shipping.shipments.index'),
        { search: search.value || undefined, per_page: perPage.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
</script>

<template>
    <Head title="Shipments" />

    <AdminLayout title="Shipments">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex justify-end">
            <Link
                :href="route('commerce.shipping.tracking')"
                class="text-sm text-brand-orange hover:underline"
            >
                Tracking view
            </Link>
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Shipments</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <button
                        type="button"
                        class="rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                        @click="openCreate"
                    >
                        Add shipment
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Tracking #</th>
                            <th>Courier</th>
                            <th>Recipient</th>
                            <th>Status</th>
                            <th>Destination</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in shipments.data" :key="item.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ item.tracking_number }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ item.courier?.name || '—' }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ item.recipient_name }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ item.status }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ item.destination || '—' }}</td>
                            <td class="admin-data-table__cell">
                                <div class="flex justify-end gap-1">
                                    <button type="button" class="admin-data-table__action" @click="openEdit(item)">Edit</button>
                                    <button
                                        type="button"
                                        class="admin-data-table__action admin-data-table__action--danger"
                                        @click="deleteTarget = item"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="shipments.data.length === 0">
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No shipments yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="shipments" :links="shipments.links" />
            </div>
        </div>

        <Modal :show="showFormModal" @close="closeFormModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit shipment' : 'Add shipment' }}</h3>
                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <InputLabel value="Tracking number" />
                        <TextInput v-model="form.tracking_number" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="form.errors.tracking_number" />
                    </div>
                    <div>
                        <InputLabel value="Courier" />
                        <select v-model="form.courier_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">None</option>
                            <option v-for="c in couriers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Recipient name" />
                        <TextInput v-model="form.recipient_name" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <InputLabel value="Destination" />
                        <TextInput v-model="form.destination" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Status" />
                        <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="pending">Pending</option>
                            <option value="shipped">Shipped</option>
                            <option value="in_transit">In transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Shipped at" />
                        <TextInput v-model="form.shipped_at" type="datetime-local" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Notes" />
                        <textarea v-model="form.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <SecondaryButton type="button" @click="closeFormModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <DeleteConfirmModal
            :show="Boolean(deleteTarget)"
            title="Delete shipment?"
            :item-name="deleteTarget?.tracking_number"
            confirm-label="Delete"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
