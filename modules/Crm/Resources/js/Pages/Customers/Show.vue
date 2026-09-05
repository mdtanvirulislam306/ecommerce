<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DateTimePicker from '@/Components/Admin/DateTimePicker.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    customer: { type: Object, required: true },
    stats: { type: Object, required: true },
    leads: { type: Array, default: () => [] },
    activities: { type: Array, default: () => [] },
    sales_orders: { type: Array, default: () => [] },
    pos_orders: { type: Array, default: () => [] },
    online_orders: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
    activityTypeOptions: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const showEdit = ref(false);
const showActivity = ref(false);
const actionForm = useForm({});

const money = (amount, currency = props.stats.currency) =>
    `${currency} ${Number(amount ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const stageMeta = {
    new: 'bg-gray-100 text-gray-600 ring-gray-200',
    contacted: 'bg-sky-50 text-sky-700 ring-sky-200',
    qualified: 'bg-amber-50 text-amber-800 ring-amber-200',
    proposal: 'bg-violet-50 text-violet-700 ring-violet-200',
    won: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    lost: 'bg-red-50 text-red-700 ring-red-200',
};

const editForm = useForm({
    name: props.customer.name,
    code: props.customer.code,
    email: props.customer.email || '',
    phone: props.customer.phone || '',
    company: props.customer.company || '',
    address: props.customer.address || '',
    customer_group_id: props.customer.customer_group_id || '',
    is_active: props.customer.is_active,
    notes: props.customer.notes || '',
});

const activityForm = useForm({
    type: 'note',
    subject: '',
    body: '',
    due_at: '',
    lead_id: '',
    customer_id: props.customer.id,
});

const openEdit = () => {
    editForm.name = props.customer.name;
    editForm.code = props.customer.code;
    editForm.email = props.customer.email || '';
    editForm.phone = props.customer.phone || '';
    editForm.company = props.customer.company || '';
    editForm.address = props.customer.address || '';
    editForm.customer_group_id = props.customer.customer_group_id || '';
    editForm.is_active = props.customer.is_active;
    editForm.notes = props.customer.notes || '';
    editForm.clearErrors();
    showEdit.value = true;
};

const saveEdit = () => {
    editForm.put(route('crm.customers.update', props.customer.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEdit.value = false;
        },
    });
};

const openActivity = () => {
    activityForm.reset();
    activityForm.type = 'note';
    activityForm.customer_id = props.customer.id;
    activityForm.lead_id = '';
    activityForm.due_at = '';
    activityForm.clearErrors();
    showActivity.value = true;
};

const saveActivity = () => {
    activityForm.post(route('crm.activities.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showActivity.value = false;
            router.reload({ only: ['activities', 'stats', 'customer'] });
        },
    });
};

const completeActivity = (activity) => {
    actionForm.post(route('crm.activities.complete', activity.id), {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['activities', 'stats'] }),
    });
};

const groupOptions = computed(() => props.groups ?? []);
const orderTotal =
    (props.stats.sales_orders ?? 0) + (props.stats.pos_orders ?? 0) + (props.stats.online_orders ?? 0);
</script>

<template>
    <Head :title="customer.name" />

    <AdminLayout :title="customer.name">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <Link :href="route('crm.customers.all')" class="text-sm text-brand-navy hover:text-brand-orange">← Customers</Link>
            <div class="flex flex-wrap gap-2">
                <SecondaryButton type="button" @click="openActivity">Log activity</SecondaryButton>
                <SecondaryButton type="button" @click="openEdit">Edit</SecondaryButton>
                <Link
                    :href="route('sales.orders.create', { customer_id: customer.id })"
                    class="inline-flex items-center rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                >
                    New sales order
                </Link>
            </div>
        </div>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Lifetime value</p>
                <p class="mt-2 text-2xl font-semibold text-brand-navy">{{ money(stats.lifetime_value) }}</p>
                <p class="mt-1 text-xs text-gray-500">Confirmed / completed sales</p>
            </div>
            <div class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Orders</p>
                <p class="mt-2 text-2xl font-semibold text-brand-navy">{{ orderTotal }}</p>
                <p class="mt-1 text-xs text-gray-500">
                    Sales {{ stats.sales_orders }} · POS {{ stats.pos_orders }} · Online {{ stats.online_orders }}
                </p>
            </div>
            <div class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Open follow-ups</p>
                <p class="mt-2 text-2xl font-semibold text-brand-orange">{{ stats.open_follow_ups }}</p>
            </div>
            <div class="admin-stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Converted leads</p>
                <p class="mt-2 text-2xl font-semibold text-brand-navy">{{ stats.leads }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
            <section class="admin-card space-y-4 text-sm">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-lg font-semibold text-brand-navy">{{ customer.name }}</p>
                            <p class="text-xs text-gray-500">{{ customer.code }}</p>
                        </div>
                        <span
                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1"
                            :class="customer.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-gray-100 text-gray-500 ring-gray-200'"
                        >
                            {{ customer.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p v-if="customer.company" class="mt-2 text-gray-600">{{ customer.company }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Contact</p>
                    <p>{{ customer.email || '—' }}</p>
                    <p>{{ customer.phone || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Group</p>
                    <p>{{ customer.customer_group_name || '—' }}</p>
                </div>
                <div v-if="customer.address">
                    <p class="text-xs text-gray-500">Address</p>
                    <p class="whitespace-pre-line">{{ customer.address }}</p>
                </div>
                <div v-if="customer.notes">
                    <p class="text-xs text-gray-500">Notes</p>
                    <p class="whitespace-pre-line">{{ customer.notes }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Customer since</p>
                    <p>{{ formatDateTime(customer.created_at) }}</p>
                </div>
            </section>

            <div class="space-y-6">
                <section class="admin-data-table">
                    <div class="admin-data-table__toolbar">
                        <div>
                            <h2 class="text-sm font-semibold text-brand-navy">Follow-ups & activity</h2>
                            <p class="text-xs text-gray-500">Open items first, then recent history</p>
                        </div>
                        <button type="button" class="text-sm font-medium text-brand-orange hover:text-brand-orange-dark" @click="openActivity">
                            Log activity
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="border-b border-gray-200 bg-gray-50/90">
                                <tr class="admin-data-table__head">
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Due</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="activity in activities" :key="activity.id" class="admin-data-table__row">
                                    <td class="admin-data-table__cell text-gray-600">{{ activity.type_label }}</td>
                                    <td class="admin-data-table__cell">
                                        <p class="font-medium text-brand-navy">{{ activity.subject }}</p>
                                        <p v-if="activity.lead_name" class="text-xs text-gray-500">Lead: {{ activity.lead_name }}</p>
                                    </td>
                                    <td class="admin-data-table__cell">
                                        <span v-if="activity.completed_at" class="text-xs text-emerald-700">Done</span>
                                        <span
                                            v-else-if="activity.due_at"
                                            class="text-sm"
                                            :class="activity.is_overdue ? 'font-medium text-red-600' : 'text-gray-600'"
                                        >
                                            {{ formatDateTime(activity.due_at) }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>
                                    <td class="admin-data-table__cell text-right">
                                        <button
                                            v-if="!activity.completed_at"
                                            type="button"
                                            class="text-xs font-medium text-brand-orange hover:underline"
                                            :disabled="actionForm.processing"
                                            @click="completeActivity(activity)"
                                        >
                                            Complete
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!activities.length">
                                    <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500">No activities yet for this customer.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-data-table">
                    <div class="admin-data-table__toolbar">
                        <div>
                            <h2 class="text-sm font-semibold text-brand-navy">Converted leads</h2>
                            <p class="text-xs text-gray-500">Prospects that became this customer</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="border-b border-gray-200 bg-gray-50/90">
                                <tr class="admin-data-table__head">
                                    <th>Name</th>
                                    <th>Stage</th>
                                    <th>Source</th>
                                    <th>Converted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="lead in leads" :key="lead.id" class="admin-data-table__row">
                                    <td class="admin-data-table__cell font-medium text-brand-navy">{{ lead.name }}</td>
                                    <td class="admin-data-table__cell">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1"
                                            :class="stageMeta[lead.stage] || stageMeta.new"
                                        >
                                            {{ lead.stage_label }}
                                        </span>
                                    </td>
                                    <td class="admin-data-table__cell text-gray-600">{{ lead.source || '—' }}</td>
                                    <td class="admin-data-table__cell text-gray-500">{{ formatDateTime(lead.converted_at || lead.created_at) }}</td>
                                </tr>
                                <tr v-if="!leads.length">
                                    <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500">No converted leads linked yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-data-table">
                    <div class="admin-data-table__toolbar">
                        <div>
                            <h2 class="text-sm font-semibold text-brand-navy">Sales orders</h2>
                            <p class="text-xs text-gray-500">{{ stats.sales_orders }} linked</p>
                        </div>
                        <Link :href="route('sales.orders.all')" class="text-sm font-medium text-brand-orange hover:text-brand-orange-dark">
                            All orders →
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="border-b border-gray-200 bg-gray-50/90">
                                <tr class="admin-data-table__head">
                                    <th>Number</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="order in sales_orders" :key="order.id" class="admin-data-table__row">
                                    <td class="admin-data-table__cell">
                                        <Link :href="route('sales.orders.show', order.id)" class="font-medium text-brand-navy hover:text-brand-orange">
                                            {{ order.number }}
                                        </Link>
                                    </td>
                                    <td class="admin-data-table__cell text-gray-600">{{ order.status_label }}</td>
                                    <td class="admin-data-table__cell">{{ money(order.grand_total, order.currency) }}</td>
                                    <td class="admin-data-table__cell text-gray-500">{{ formatDateTime(order.created_at) }}</td>
                                </tr>
                                <tr v-if="!sales_orders.length">
                                    <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500">No sales orders linked.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-data-table">
                    <div class="admin-data-table__toolbar">
                        <div>
                            <h2 class="text-sm font-semibold text-brand-navy">POS sales</h2>
                            <p class="text-xs text-gray-500">{{ stats.pos_orders }} linked</p>
                        </div>
                        <Link :href="route('pos.orders.index')" class="text-sm font-medium text-brand-orange hover:text-brand-orange-dark">
                            All POS →
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="border-b border-gray-200 bg-gray-50/90">
                                <tr class="admin-data-table__head">
                                    <th>Number</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="order in pos_orders" :key="order.id" class="admin-data-table__row">
                                    <td class="admin-data-table__cell">
                                        <Link :href="route('pos.orders.show', order.id)" class="font-medium text-brand-navy hover:text-brand-orange">
                                            {{ order.number }}
                                        </Link>
                                    </td>
                                    <td class="admin-data-table__cell text-gray-600">{{ order.status_label }}</td>
                                    <td class="admin-data-table__cell">{{ money(order.grand_total, order.currency) }}</td>
                                    <td class="admin-data-table__cell text-gray-500">{{ formatDateTime(order.created_at) }}</td>
                                </tr>
                                <tr v-if="!pos_orders.length">
                                    <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500">No POS sales linked.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-data-table">
                    <div class="admin-data-table__toolbar">
                        <div>
                            <h2 class="text-sm font-semibold text-brand-navy">Online orders</h2>
                            <p class="text-xs text-gray-500">{{ stats.online_orders }} linked</p>
                        </div>
                        <Link :href="route('ecommerce.online-orders.index')" class="text-sm font-medium text-brand-orange hover:text-brand-orange-dark">
                            All online →
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="border-b border-gray-200 bg-gray-50/90">
                                <tr class="admin-data-table__head">
                                    <th>Number</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="order in online_orders" :key="order.id" class="admin-data-table__row">
                                    <td class="admin-data-table__cell">
                                        <Link :href="route('ecommerce.online-orders.show', order.id)" class="font-medium text-brand-navy hover:text-brand-orange">
                                            {{ order.number }}
                                        </Link>
                                    </td>
                                    <td class="admin-data-table__cell text-gray-600">{{ order.status_label }}</td>
                                    <td class="admin-data-table__cell">{{ money(order.grand_total, order.currency) }}</td>
                                    <td class="admin-data-table__cell text-gray-500">{{ formatDateTime(order.created_at) }}</td>
                                </tr>
                                <tr v-if="!online_orders.length">
                                    <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500">No online orders linked.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <Modal :show="showEdit" max-width="lg" @close="showEdit = false">
            <form class="p-6" @submit.prevent="saveEdit">
                <h3 class="text-lg font-semibold text-brand-navy">Edit customer</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel for="edit-name" value="Name" />
                        <TextInput id="edit-name" v-model="editForm.name" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="editForm.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="edit-code" value="Code" />
                        <TextInput id="edit-code" v-model="editForm.code" class="mt-1 block w-full" />
                        <InputError class="mt-1" :message="editForm.errors.code" />
                    </div>
                    <div>
                        <InputLabel value="Group" />
                        <SearchableSelect
                            v-model="editForm.customer_group_id"
                            class="mt-1"
                            :options="groupOptions"
                            placeholder="No group"
                            clearable
                        />
                    </div>
                    <div>
                        <InputLabel for="edit-email" value="Email" />
                        <TextInput id="edit-email" v-model="editForm.email" type="email" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="edit-phone" value="Phone" />
                        <TextInput id="edit-phone" v-model="editForm.phone" class="mt-1 block w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="edit-company" value="Company" />
                        <TextInput id="edit-company" v-model="editForm.company" class="mt-1 block w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="edit-address" value="Address" />
                        <textarea id="edit-address" v-model="editForm.address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="edit-notes" value="Notes" />
                        <textarea id="edit-notes" v-model="editForm.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange" />
                    </div>
                    <label class="flex items-center gap-2 sm:col-span-2">
                        <Checkbox v-model:checked="editForm.is_active" />
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showEdit = false">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="editForm.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="showActivity" max-width="lg" @close="showActivity = false">
            <form class="p-6" @submit.prevent="saveActivity">
                <h3 class="text-lg font-semibold text-brand-navy">Log activity</h3>
                <div class="mt-4 space-y-4">
                    <div>
                        <InputLabel value="Type" />
                        <select v-model="activityForm.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange">
                            <option v-for="opt in activityTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="act-subject" value="Subject" />
                        <TextInput id="act-subject" v-model="activityForm.subject" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="activityForm.errors.subject" />
                    </div>
                    <div>
                        <InputLabel for="act-body" value="Details" />
                        <textarea id="act-body" v-model="activityForm.body" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange" />
                    </div>
                    <div>
                        <InputLabel value="Due at" />
                        <DateTimePicker v-model="activityForm.due_at" class="mt-1" />
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showActivity = false">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="activityForm.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
