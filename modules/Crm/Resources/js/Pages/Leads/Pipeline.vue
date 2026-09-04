<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    board: { type: Object, required: true },
    stageOptions: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const showModal = ref(false);
const actionForm = useForm({});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    company: '',
    source: '',
    stage: 'new',
    customer_group_id: '',
    notes: '',
});

const openCreate = () => {
    form.reset();
    form.stage = 'new';
    form.clearErrors();
    showModal.value = true;
};

const save = () => {
    form.post(route('crm.leads.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
        },
    });
};

const moveStage = (lead, nextStage) => {
    if (lead.stage === nextStage) return;
    actionForm.transform(() => ({ stage: nextStage })).post(route('crm.leads.stage', lead.id), {
        preserveScroll: true,
    });
};

const convertLead = (lead) => {
    if (!confirm(`Convert ${lead.name} to a customer?`)) return;
    actionForm.post(route('crm.leads.convert', lead.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Lead Pipeline" />

    <AdminLayout title="Lead Pipeline">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <Link :href="route('crm.leads.all')" class="text-sm text-brand-navy hover:text-brand-orange">← All leads</Link>
            <PrimaryButton type="button" @click="openCreate">Add lead</PrimaryButton>
        </div>

        <div class="flex gap-3 overflow-x-auto pb-2">
            <section
                v-for="stage in stageOptions"
                :key="stage.value"
                class="min-w-[240px] flex-1 rounded-xl border border-gray-100 bg-gray-50/80 p-3"
            >
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-brand-navy">{{ stage.label }}</h2>
                    <span class="text-xs text-gray-500">{{ (board[stage.value] || []).length }}</span>
                </div>
                <div class="space-y-2">
                    <article
                        v-for="lead in board[stage.value] || []"
                        :key="lead.id"
                        class="rounded-lg border border-gray-100 bg-white p-3 shadow-sm"
                    >
                        <p class="text-sm font-medium text-brand-navy">{{ lead.name }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ lead.company || lead.email || lead.phone || '—' }}</p>
                        <p v-if="lead.source" class="mt-1 text-[11px] text-gray-400">{{ lead.source }}</p>
                        <div class="mt-3 flex flex-wrap gap-1">
                            <select
                                v-if="stage.value !== 'won'"
                                class="max-w-full rounded border border-gray-200 text-[11px]"
                                :value="lead.stage"
                                @change="moveStage(lead, $event.target.value)"
                            >
                                <option v-for="s in stageOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                                <option value="lost">Lost</option>
                            </select>
                            <button
                                v-if="lead.can_convert"
                                type="button"
                                class="rounded bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700"
                                @click="convertLead(lead)"
                            >
                                Convert
                            </button>
                        </div>
                    </article>
                    <p v-if="!(board[stage.value] || []).length" class="py-6 text-center text-xs text-gray-400">Empty</p>
                </div>
            </section>
        </div>

        <Modal :show="showModal" max-width="md" @close="showModal = false">
            <form class="space-y-4 p-6" @submit.prevent="save">
                <h2 class="text-lg font-semibold text-brand-navy">Add lead</h2>
                <div>
                    <InputLabel value="Name" />
                    <TextInput v-model="form.name" class="mt-1 block w-full" required />
                    <InputError class="mt-1" :message="form.errors.name" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Email" />
                        <TextInput v-model="form.email" type="email" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Phone" />
                        <TextInput v-model="form.phone" class="mt-1 block w-full" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Company" />
                    <TextInput v-model="form.company" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel value="Customer group" />
                    <select v-model="form.customer_group_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">None</option>
                        <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
