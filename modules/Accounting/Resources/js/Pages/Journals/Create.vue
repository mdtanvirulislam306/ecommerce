<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    accounts: { type: Array, default: () => [] },
});

const form = useForm({
    entry_date: new Date().toISOString().slice(0, 10),
    memo: '',
    currency: 'BDT',
    lines: [
        { ledger_account_id: '', debit: '', credit: '', memo: '' },
        { ledger_account_id: '', debit: '', credit: '', memo: '' },
    ],
});

const totalDebit = computed(() => form.lines.reduce((sum, line) => sum + Number(line.debit || 0), 0));
const totalCredit = computed(() => form.lines.reduce((sum, line) => sum + Number(line.credit || 0), 0));
const balanced = computed(() => Math.abs(totalDebit.value - totalCredit.value) < 0.00005 && totalDebit.value > 0);

const addLine = () => {
    form.lines.push({ ledger_account_id: '', debit: '', credit: '', memo: '' });
};

const removeLine = (index) => {
    if (form.lines.length <= 2) return;
    form.lines.splice(index, 1);
};

const submit = () => {
    form.post(route('accounting.journal-entries.store'));
};
</script>

<template>
    <Head title="New Journal" />

    <AdminLayout title="New Journal Entry">
        <div class="mb-4">
            <Link :href="route('accounting.journal-entries.index')" class="text-sm text-brand-navy hover:text-brand-orange">
                ← Journals
            </Link>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <section class="admin-card grid gap-4 sm:grid-cols-3">
                <div>
                    <InputLabel value="Entry date" />
                    <TextInput v-model="form.entry_date" type="date" class="mt-1 block w-full" required />
                    <InputError class="mt-1" :message="form.errors.entry_date" />
                </div>
                <div class="sm:col-span-2">
                    <InputLabel value="Memo" />
                    <TextInput v-model="form.memo" class="mt-1 block w-full" />
                </div>
            </section>

            <section class="admin-card space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-brand-navy">Lines</h2>
                    <button type="button" class="text-sm text-brand-orange hover:underline" @click="addLine">+ Add line</button>
                </div>

                <div v-for="(line, index) in form.lines" :key="index" class="grid gap-2 rounded-lg border border-gray-100 p-3 sm:grid-cols-12">
                    <div class="sm:col-span-4">
                        <InputLabel value="Account" />
                        <select v-model="line.ledger_account_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                            <option value="" disabled>Select…</option>
                            <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.code }} — {{ a.name }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Debit" />
                        <TextInput v-model="line.debit" type="number" min="0" step="any" class="mt-1 block w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Credit" />
                        <TextInput v-model="line.credit" type="number" min="0" step="any" class="mt-1 block w-full" />
                    </div>
                    <div class="sm:col-span-3">
                        <InputLabel value="Line memo" />
                        <TextInput v-model="line.memo" class="mt-1 block w-full" />
                    </div>
                    <div class="flex items-end sm:col-span-1">
                        <button
                            v-if="form.lines.length > 2"
                            type="button"
                            class="mb-1 text-xs text-red-600"
                            @click="removeLine(index)"
                        >
                            Remove
                        </button>
                    </div>
                </div>

                <InputError :message="form.errors.lines" />

                <div class="flex justify-between text-sm">
                    <span :class="balanced ? 'text-emerald-700' : 'text-red-600'">
                        {{ balanced ? 'Balanced' : 'Unbalanced' }}
                    </span>
                    <span class="text-brand-navy">
                        Dr {{ totalDebit.toFixed(2) }} / Cr {{ totalCredit.toFixed(2) }}
                    </span>
                </div>
            </section>

            <div class="flex justify-end gap-2">
                <Link :href="route('accounting.journal-entries.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton type="submit" :disabled="form.processing || !balanced">Post journal</PrimaryButton>
            </div>
        </form>
    </AdminLayout>
</template>
