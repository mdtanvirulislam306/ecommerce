<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    channel: { type: String, required: true },
    preferences: { type: Array, default: () => [] },
});
const flash = computed(() => usePage().props.flash);
const form = useForm({ preferences: props.preferences.map((p) => ({ ...p })) });
const submit = () => form.put(route(`notifications.${props.channel}.update`), { preserveScroll: true });
</script>
<template>
    <Head :title="`${channel} notifications`" />
    <AdminLayout :title="`${channel} channel`">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <form class="admin-card max-w-lg space-y-3" @submit.prevent="submit">
            <label v-for="(pref, idx) in form.preferences" :key="pref.channel" class="flex items-center gap-2 text-sm">
                <Checkbox v-model:checked="form.preferences[idx].enabled" />
                {{ pref.channel }}
            </label>
            <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
        </form>
    </AdminLayout>
</template>