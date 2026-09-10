<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    widget: { type: Object, required: true },
    editing: { type: Boolean, default: false },
    pageSlug: { type: String, default: '' },
});

const form = useForm({
    type: 'contact',
    widget_id: props.widget.id,
    name: '',
    email: '',
    message: '',
});

const submit = () => {
    if (props.editing || !props.pageSlug) {
        return;
    }
    form.post(route('shop.pages.forms.store', props.pageSlug), {
        preserveScroll: true,
        onSuccess: () => form.reset('name', 'email', 'message'),
    });
};
</script>

<template>
    <div class="mx-auto max-w-xl rounded-2xl border border-gray-200 bg-white p-6">
        <h2 class="text-2xl font-semibold text-brand-navy">{{ widget.settings?.heading }}</h2>
        <form class="mt-4 space-y-3" @submit.prevent="submit">
            <div>
                <InputLabel value="Name" />
                <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-md border-gray-300 text-sm" :disabled="editing" />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>
            <div>
                <InputLabel value="Email" />
                <input v-model="form.email" type="email" required class="mt-1 block w-full rounded-md border-gray-300 text-sm" :disabled="editing" />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>
            <div>
                <InputLabel value="Message" />
                <textarea v-model="form.message" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 text-sm" :disabled="editing" />
                <InputError class="mt-1" :message="form.errors.message" />
            </div>
            <button
                type="submit"
                class="rounded-lg bg-brand-orange px-4 py-2 text-sm font-semibold text-white hover:bg-brand-orange-dark disabled:opacity-50"
                :disabled="editing || form.processing"
            >
                {{ widget.settings?.button_label || 'Send message' }}
            </button>
            <p v-if="editing" class="text-xs text-gray-400">Forms submit on the published page.</p>
        </form>
    </div>
</template>
